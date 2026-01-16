<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Widget;
use App\Models\ChatSession;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'widgetId' => 'nullable|string',
            'history' => 'nullable|array',
            'sessionId' => 'nullable|string',
        ]);

        $message = $request->input('message');
        $widgetId = $request->input('widgetId', 'default');
        $history = $request->input('history', []);
        $sessionId = $request->input('sessionId', 'sess_' . Str::random(16));

        // Load widget and knowledge base
        $widget = Widget::where('slug', $widgetId)->with(['knowledgeBase.faqs', 'user.plan'])->first();

        if (!$widget) {
            // Fallback to demo knowledge base from JSON
            $kbPath = storage_path('app/data/knowledge-base.json');
            if (!file_exists($kbPath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Widget not found'
                ], 404);
            }
            $kb = json_decode(file_get_contents($kbPath), true);
        } else {
            // Check quota before processing (skip for landing page widget)
            $isLandingPage = $widget->slug === 'landing-page-default';

            if (!$isLandingPage) {
                $user = $widget->user;

                // Skip quota check if user not found (shouldn't happen but safety check)
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Widget owner not found'
                    ], 404);
                }

                // Check if user is suspended or banned
                if (in_array($user->status, ['suspended', 'banned'])) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Widget temporarily unavailable'
                    ], 403);
                }

                // Check quota if user has a plan
                $plan = $user->plan;
                if ($plan && $user->monthly_message_used >= $plan->max_messages_per_month) {
                    return response()->json([
                        'success' => false,
                        'error' => 'quota_exceeded',
                        'message' => 'Maaf, kuota pesan bulanan telah habis. Silakan hubungi pemilik website.',
                        'quota' => [
                            'used' => $user->monthly_message_used,
                            'limit' => $plan->max_messages_per_month,
                            'reset_date' => now()->startOfMonth()->addMonth()->format('d M Y')
                        ]
                    ], 429);
                }
            }

            $kb = $this->buildKnowledgeBaseArray($widget);
        }

        // Build system prompt
        $systemPrompt = $this->buildSystemPrompt($kb, $sessionId);

        // Format history (last 10 messages)
        $formattedHistory = array_slice($history, -10);
        $formattedHistory[] = ['role' => 'user', 'content' => $message];

        // Get model based on user's plan AI tier (LLM Abstraction)
        $model = $this->getModelForUser($widget);

        // Strategy 2: Trigger System - Insert lead collection prompt based on conditions
        $settings = $kb['settings'] ?? [];
        if (!empty($settings['lead_trigger_enabled'])) {
            $triggerAfter = $settings['lead_trigger_after_message'] ?? 3;
            $triggerKeywords = $settings['lead_trigger_keywords'] ?? '';
            $keywordList = array_map('trim', explode(',', strtolower($triggerKeywords)));

            $messageCount = count($history) + 1; // Including current message
            $messageLower = strtolower($message);

            $shouldTrigger = false;

            // Check message count trigger
            if ($messageCount >= $triggerAfter) {
                $shouldTrigger = true;
            }

            // Check keyword trigger
            foreach ($keywordList as $keyword) {
                if (!empty($keyword) && strpos($messageLower, $keyword) !== false) {
                    $shouldTrigger = true;
                    break;
                }
            }

            // If triggered, add instruction to the last message context
            if ($shouldTrigger) {
                $leadInstruction = "\n[PENTING: Setelah menjawab pertanyaan ini, tanyakan nama/email/telepon user dengan sopan]";
                $systemPrompt .= $leadInstruction;
            }
        }

        // Call OpenRouter
        try {
            $response = $this->callOpenRouter($systemPrompt, $formattedHistory, $model);

            // Log full response for debugging
            \Log::info('OpenRouter Response', ['model' => $model, 'response' => $response]);

            $responseText = $response['choices'][0]['message']['content'] ?? null;

            if (!$responseText) {
                // Check if there's an error in response
                if (isset($response['error'])) {
                    \Log::error('OpenRouter API Error', ['error' => $response['error']]);
                    throw new \Exception($response['error']['message'] ?? 'API Error');
                }
                throw new \Exception('No response from AI');
            }

            // Save to database if widget exists
            if ($widget) {
                $this->saveChatMessage($widget->id, $sessionId, $message, $responseText, $model, $response['usage'] ?? []);

                // Increment user's monthly message quota (skip for landing page widget - unlimited)
                if ($widget->user && $widget->slug !== 'landing-page-default') {
                    $widget->user->increment('monthly_message_used');
                }
            }

            return response()->json([
                'success' => true,
                'response' => $responseText,
                'sessionId' => $sessionId,
                'usage' => $response['usage'] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('Chat API Error', [
                'message' => $e->getMessage(),
                'model' => $model,
                'widget' => $widgetId
            ]);

            return response()->json([
                'success' => true,
                'response' => 'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi dalam beberapa saat.',
                'sessionId' => $sessionId,
                'fallback' => true,
                'debug' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    private function buildKnowledgeBaseArray($widget)
    {
        $kb = $widget->knowledgeBase;

        return [
            'knowledge_base_id' => $kb->id,
            'company' => [
                'name' => $kb->company_name ?? 'Perusahaan',
                'description' => $kb->company_description ?? '',
            ],
            'persona' => [
                'name' => $kb->persona_name,
                'tone' => $kb->persona_tone ?? 'friendly',
                'language' => 'id',
            ],
            'faqs' => $kb->faqs->map(fn($faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])->toArray(),
            'custom_instructions' => $kb->custom_instructions,
            'settings' => $widget->settings ?? [],
        ];
    }

    private function buildSystemPrompt($kb, $sessionId)
    {
        $company = $kb['company'] ?? [];
        $persona = $kb['persona'] ?? [];
        $faqs = $kb['faqs'] ?? [];

        $companyName = $company['name'] ?? 'Perusahaan';
        $companyDesc = $company['description'] ?? '';

        // Random Indonesian name
        $names = ['Rina', 'Dian', 'Sari', 'Mega', 'Putri', 'Indah', 'Maya', 'Citra'];
        $seed = abs(crc32($sessionId));
        $randomName = $names[$seed % count($names)];

        $personaName = $persona['name'] ?? $randomName;
        $personaTone = $persona['tone'] ?? 'friendly';

        $prompt = "Kamu adalah {$personaName}, seorang Customer Service untuk {$companyName}.\n";
        $prompt .= "Deskripsi perusahaan: {$companyDesc}\n\n";
        $prompt .= "## Kepribadian & Gaya Bicara\n";
        $prompt .= "- Kamu WAJIB menggunakan bahasa Indonesia yang santai, natural, dan akrab.\n";
        $prompt .= "- WAJIB menyapa user dengan sebutan 'Kak' atau 'Kakak'.\n";
        $prompt .= "- Gunakan emoji sesekali yang relevan 😊.\n";
        $prompt .= "- Nada bicara: {$personaTone}\n\n";

        if (!empty($faqs)) {
            $prompt .= "## FAQ\n";
            foreach ($faqs as $faq) {
                $prompt .= "Q: {$faq['question']}\n";
                $prompt .= "A: {$faq['answer']}\n\n";
            }
        }

        // Load uploaded documents if knowledge_base_id exists
        if (isset($kb['knowledge_base_id'])) {
            $documents = \App\Models\KnowledgeDocument::where('knowledge_base_id', $kb['knowledge_base_id'])
                ->where('status', 'completed')
                ->get();

            if ($documents->isNotEmpty()) {
                $prompt .= "## Dokumen & Informasi Tambahan\n";
                foreach ($documents as $doc) {
                    $prompt .= "Sumber: {$doc->name}\n";
                    $chunks = is_array($doc->chunks) ? $doc->chunks : json_decode($doc->chunks, true);
                    if ($chunks && is_array($chunks)) {
                        // Use first 2 chunks to avoid token limits
                        $selectedChunks = array_slice($chunks, 0, 2);
                        foreach ($selectedChunks as $chunk) {
                            $prompt .= $chunk . "\n\n";
                        }
                    }
                }
            }
        }

        if (!empty($kb['custom_instructions'])) {
            $prompt .= "## Instruksi Tambahan\n{$kb['custom_instructions']}\n\n";
        }

        // Lead Collection - Strategy 1: Prompt Engineering
        $settings = $kb['settings'] ?? [];
        if (!empty($settings['lead_prompt_enabled'])) {
            $prompt .= "## Instruksi Lead Collection\n";
            $prompt .= "Di sela percakapan atau menjelang akhir, tanyakan data berikut secara sopan dan tidak memaksa:\n";

            if (!empty($settings['lead_ask_name'])) {
                $prompt .= "- Nama Lengkap: \"Btw Kak, boleh tau nama lengkap Kakak?\"\n";
            }
            if (!empty($settings['lead_ask_email'])) {
                $prompt .= "- Email: \"Kalau ada info lanjut, mau kita kirim ke email Kak. Boleh tau emailnya?\"\n";
            }
            if (!empty($settings['lead_ask_phone'])) {
                $prompt .= "- Nomor HP: \"Supaya lebih gampang dihubungi, boleh minta nomor WA Kak?\"\n";
            }

            $prompt .= "- Jangan tanyakan sekaligus. Selingi dengan jawaban topik.\n";
            $prompt .= "- Setelah dapat data, ucapkan terima kasih.\n\n";
        }

        $prompt .= "## Aturan Penting\n";
        $prompt .= "- JANGAN membuat informasi yang tidak ada di knowledge base\n";
        $prompt .= "- Jika ditanya di luar konteks, arahkan kembali ke topik {$companyName}\n";

        return $prompt;
    }

    private function callOpenRouter($systemPrompt, $messages, $model)
    {
        $allMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$messages
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Cekat SaaS',
        ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $allMessages,
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                ]);

        return $response->json();
    }

    private function saveChatMessage($widgetId, $sessionId, $userMessage, $aiResponse, $model, $usage)
    {
        $session = ChatSession::firstOrCreate(
            ['widget_id' => $widgetId, 'visitor_uuid' => $sessionId],
            [
                'started_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        );

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $aiResponse,
            'tokens_used' => $usage['total_tokens'] ?? 0,
            'model_used' => $model,
        ]);
    }

    /**
     * Get LLM model based on user's plan AI tier (LLM Abstraction)
     * 
     * This hides specific model names from users, instead mapping
     * their plan's "AI Quality Tier" to actual models.
     */
    private function getModelForUser($widget)
    {
        // Default fallback model
        $defaultModel = 'nvidia/nemotron-3-nano-30b-a3b:free';

        if (!$widget) {
            return $defaultModel;
        }

        // Special handling for landing page widget - use direct model from settings
        if ($widget->slug === 'landing-page-default') {
            $settings = $widget->settings ?? [];
            $model = $settings['model'] ?? $defaultModel;
            \Log::info('Landing Page Model Selection', ['model' => $model]);
            return $model;
        }

        if (!$widget->user) {
            return $defaultModel;
        }

        $user = $widget->user;
        $plan = $user->plan;

        // Get AI tier from user's plan (basic, standard, advanced, premium)
        $aiTier = $plan->ai_tier ?? 'basic';

        // Get tier mapping from admin settings
        // Setting::get may return array (if type=json) or string
        $mappingData = \App\Models\Setting::get('ai_tier_mapping');

        if ($mappingData) {
            // Handle both array and JSON string
            $mapping = is_array($mappingData) ? $mappingData : json_decode($mappingData, true);

            if (is_array($mapping) && isset($mapping[$aiTier])) {
                \Log::info('AI Tier Model Selection', [
                    'user_id' => $user->id,
                    'plan' => $plan->name,
                    'ai_tier' => $aiTier,
                    'model' => $mapping[$aiTier],
                ]);
                return $mapping[$aiTier];
            }
        }

        // Fallback mapping if settings not configured
        $defaultMapping = [
            'basic' => 'nvidia/nemotron-3-nano-30b-a3b:free',
            'standard' => 'openai/gpt-4o-mini',
            'advanced' => 'openai/gpt-4o-mini',
            'premium' => 'openai/gpt-4o-mini',
        ];

        \Log::info('AI Tier Model Selection (fallback)', [
            'user_id' => $user->id,
            'plan' => $plan->name,
            'ai_tier' => $aiTier,
            'model' => $defaultMapping[$aiTier] ?? $defaultModel,
        ]);

        return $defaultMapping[$aiTier] ?? $defaultModel;
    }
}
