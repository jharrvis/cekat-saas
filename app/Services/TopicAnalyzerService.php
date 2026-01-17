<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TopicAnalyzerService
{
    /**
     * Analyze topics from chat history using AI
     */
    public function analyzeTopics(User $user, $widgetIds, $forceRefresh = false)
    {
        // Cache key unique per user
        $cacheKey = 'user_topics_' . $user->id;

        // Check cache first (cache for 6 hours)
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get sample messages from chat history
        $messages = ChatMessage::whereHas('session', function ($q) use ($widgetIds) {
            $q->whereIn('widget_id', $widgetIds);
        })
            ->where('role', 'user')
            ->latest()
            ->limit(100)
            ->pluck('content')
            ->toArray();

        if (empty($messages)) {
            return [];
        }

        // Try AI analysis first, fallback to word frequency
        try {
            $topics = $this->analyzeWithAI($user, $messages);

            if (!empty($topics)) {
                // Cache for 6 hours
                Cache::put($cacheKey, $topics, now()->addHours(6));
                return $topics;
            }
        } catch (\Exception $e) {
            Log::warning('AI Topic Analysis failed, using fallback', ['error' => $e->getMessage()]);
        }

        // Fallback to word frequency analysis
        $topics = $this->analyzeWithWordFrequency($messages);
        Cache::put($cacheKey, $topics, now()->addHours(6));

        return $topics;
    }

    /**
     * Basic analysis using word frequency only (for free users)
     * No AI calls - just word/bigram frequency
     */
    public function analyzeTopicsBasic(User $user, $widgetIds)
    {
        $cacheKey = 'user_topics_' . $user->id;

        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get sample messages from chat history
        $messages = ChatMessage::whereHas('session', function ($q) use ($widgetIds) {
            $q->whereIn('widget_id', $widgetIds);
        })
            ->where('role', 'user')
            ->latest()
            ->limit(100)
            ->pluck('content')
            ->toArray();

        if (empty($messages)) {
            return [];
        }

        // Use word frequency only (no AI)
        $topics = $this->analyzeWithWordFrequency($messages);
        Cache::put($cacheKey, $topics, now()->addHours(6));

        return $topics;
    }

    /**
     * Analyze topics using AI (OpenRouter)
     */
    private function analyzeWithAI(User $user, array $messages)
    {
        $apiKey = config('services.openrouter.api_key');

        if (!$apiKey) {
            throw new \Exception('OpenRouter API key not configured');
        }

        // Get model based on user's plan
        $model = $this->getModelForUser($user);

        // Prepare sample messages (limit to avoid token issues)
        $sampleMessages = array_slice($messages, 0, 50);
        $messagesText = implode("\n", array_map(function ($msg, $i) {
            return ($i + 1) . ". " . $msg;
        }, $sampleMessages, array_keys($sampleMessages)));

        $prompt = <<<PROMPT
Kamu adalah analis topik. Analisis pesan-pesan customer berikut dan identifikasi 4 topik utama yang paling sering ditanyakan.

PESAN CUSTOMER:
{$messagesText}

INSTRUKSI:
- Identifikasi 4 topik utama yang paling sering dibahas
- Untuk setiap topik, buat nama yang singkat tapi deskriptif (3-5 kata)
- Hitung persentase relatif tiap topik (total 100%)
- Contoh nama topik yang BAIK: "Harga paket layanan", "Cara order produk", "Status pengiriman"
- Contoh nama topik yang BURUK: "Berapa", "Harga", "Order"

FORMAT OUTPUT (JSON array):
[
  {"name": "Nama Topik 1", "percent": 40},
  {"name": "Nama Topik 2", "percent": 30},
  {"name": "Nama Topik 3", "percent": 20},
  {"name": "Nama Topik 4", "percent": 10}
]

Jawab HANYA dengan JSON array, tanpa penjelasan tambahan.
PROMPT;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Cekat SaaS Topic Analyzer',
        ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->status());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Parse JSON from response
        $jsonMatch = [];
        if (preg_match('/\[[\s\S]*\]/', $content, $jsonMatch)) {
            $topics = json_decode($jsonMatch[0], true);

            if (is_array($topics) && !empty($topics)) {
                // Normalize and validate
                $normalizedTopics = [];
                $total = array_sum(array_column($topics, 'percent'));

                foreach ($topics as $index => $topic) {
                    if (isset($topic['name']) && isset($topic['percent'])) {
                        $normalizedTopics[] = [
                            'name' => $topic['name'],
                            'count' => 0, // AI doesn't provide exact count
                            'percent' => $total > 0
                                ? round(($topic['percent'] / $total) * 100)
                                : round(100 / count($topics)),
                        ];
                    }
                }

                return array_slice($normalizedTopics, 0, 4);
            }
        }

        throw new \Exception('Failed to parse AI response');
    }

    /**
     * Fallback: Analyze using word frequency (improved version)
     */
    private function analyzeWithWordFrequency(array $messages)
    {
        $stopWords = [
            'yang',
            'dan',
            'di',
            'ini',
            'itu',
            'dengan',
            'untuk',
            'pada',
            'adalah',
            'dari',
            'ke',
            'ya',
            'tidak',
            'ada',
            'bisa',
            'saya',
            'anda',
            'kamu',
            'kami',
            'mereka',
            'mau',
            'ingin',
            'apa',
            'bagaimana',
            'kapan',
            'dimana',
            'siapa',
            'mengapa',
            'halo',
            'hai',
            'hi',
            'hello',
            'ok',
            'oke',
            'baik',
            'terima',
            'kasih',
            'tolong',
            'mohon',
            'please',
            'thanks',
            'thank',
            'you',
            'the',
            'is',
            'are',
            'was',
            'were',
            'berapa',
            'gimana',
            'kenapa',
            'kapan',
            'mana',
            'siapa',
            'boleh',
            'bisa',
            'dong',
            'deh',
            'nih',
            'kan',
            'lah',
            'kah',
            'gak',
            'ga',
            'nggak',
            'enggak',
            'jika',
            'kalau',
            'atau',
            'tapi',
            'tetapi',
            'namun',
            'karena',
            'sebab',
            'jadi',
        ];

        // Extract bigrams (2-word phrases) for better context
        $phraseCounts = [];

        foreach ($messages as $msg) {
            $text = strtolower($msg);
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

            // Filter out stop words and short words
            $filteredWords = array_filter($words, function ($word) use ($stopWords) {
                return strlen($word) >= 3 && !in_array($word, $stopWords);
            });

            $filteredWords = array_values($filteredWords);

            // Create bigrams
            for ($i = 0; $i < count($filteredWords) - 1; $i++) {
                $bigram = $filteredWords[$i] . ' ' . $filteredWords[$i + 1];
                if (!isset($phraseCounts[$bigram])) {
                    $phraseCounts[$bigram] = 0;
                }
                $phraseCounts[$bigram]++;
            }

            // Also count single important words
            foreach ($filteredWords as $word) {
                if (strlen($word) >= 4) {
                    if (!isset($phraseCounts[$word])) {
                        $phraseCounts[$word] = 0;
                    }
                    $phraseCounts[$word]++;
                }
            }
        }

        // Sort by frequency
        arsort($phraseCounts);

        // Get top phrases, prioritizing bigrams
        $topPhrases = array_slice($phraseCounts, 0, 10, true);
        $total = array_sum($topPhrases) ?: 1;

        $topics = [];
        foreach ($topPhrases as $phrase => $count) {
            if ($count < 2)
                continue;
            if (count($topics) >= 4)
                break;

            $topics[] = [
                'name' => ucwords($phrase),
                'count' => $count,
                'percent' => round(($count / $total) * 100),
            ];
        }

        return $topics;
    }

    /**
     * Get AI model based on user's plan
     */
    private function getModelForUser(User $user)
    {
        $plan = $user->plan;

        if (!$plan) {
            // Free tier - use cheap/free model
            return 'nvidia/nemotron-3-nano-30b-a3b:free';
        }

        $aiTier = $plan->ai_tier ?? 'basic';

        // Use appropriate model based on tier
        // For topic analysis, we don't need the most powerful model
        $modelMapping = [
            'basic' => 'nvidia/nemotron-3-nano-30b-a3b:free',
            'standard' => 'openai/gpt-4o-mini',
            'advanced' => 'openai/gpt-4o-mini',
            'premium' => 'openai/gpt-4o-mini',
        ];

        return $modelMapping[$aiTier] ?? 'nvidia/nemotron-3-nano-30b-a3b:free';
    }

    /**
     * Clear cached topics for a user
     */
    public function clearCache(User $user)
    {
        Cache::forget('user_topics_' . $user->id);
    }
}
