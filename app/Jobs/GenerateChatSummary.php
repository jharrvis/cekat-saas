<?php

namespace App\Jobs;

use App\Models\ChatSession;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateChatSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ChatSession $session;

    /**
     * Create a new job instance.
     */
    public function __construct(ChatSession $session)
    {
        $this->session = $session;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messages = $this->session->messages()->orderBy('created_at', 'asc')->get();

        if ($messages->isEmpty()) {
            return;
        }

        // Build conversation text
        $conversationText = $messages->map(function ($msg) {
            $role = $msg->role === 'user' ? 'Customer' : 'AI';
            return "{$role}: {$msg->content}";
        })->join("\n");

        // Get model from settings
        $model = Setting::get('default_ai_model', 'openai/gpt-4o-mini');

        // Generate summary using OpenRouter
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openrouter.api_key'),
                'HTTP-Referer' => config('app.url'),
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Kamu adalah asisten yang membuat ringkasan percakapan customer service. Buatkan ringkasan singkat (maksimal 3 kalimat) dalam Bahasa Indonesia yang mencakup: topik utama, kebutuhan customer, dan hasil percakapan.'
                            ],
                            [
                                'role' => 'user',
                                'content' => "Buatkan ringkasan dari percakapan berikut:\n\n{$conversationText}"
                            ]
                        ],
                        'max_tokens' => 200,
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                $summary = $data['choices'][0]['message']['content'] ?? null;

                if ($summary) {
                    $this->session->update([
                        'summary' => $summary,
                        'summary_generated_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate chat summary', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
