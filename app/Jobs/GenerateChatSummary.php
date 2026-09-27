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
        $model = Setting::get('default_ai_model', config('services.openrouter.default_model'));

        // Generate summary using OpenRouter
        try {
            $data = \App\Services\OpenRouterClient::chatCompletion([
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
            ], 60, 'Cekat SaaS Summary');

            if (empty($data['error'])) {
                $summary = $data['choices'][0]['message']['content'] ?? null;

                if ($summary) {
                    $this->session->update([
                        'summary' => $summary,
                        'summary_generated_at' => now(),
                    ]);
                }
            } else {
                Log::error('Failed to generate chat summary', [
                    'session_id' => $this->session->id,
                    'error' => $data['error'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate chat summary', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
