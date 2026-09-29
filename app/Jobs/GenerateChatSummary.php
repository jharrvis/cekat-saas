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
     *
     * Accepts a model or a bare id so legacy call sites that dispatch the
     * session id (ChatInbox) keep working instead of throwing a TypeError.
     */
    public function __construct(ChatSession|int|string $session)
    {
        $this->session = $session instanceof ChatSession
            ? $session
            : ChatSession::findOrFail($session);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Bounded input: never send the full transcript to the provider -
        // last 20 messages, hard-trimmed to ~6.000 characters.
        $messages = $this->session->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->slice(-20);

        if ($messages->isEmpty()) {
            return;
        }

        // Build conversation text. Role labels matter: they end up in the
        // summary, so the service side is named "layanan customer service"
        // (never "AI"/"chatbot") to keep the resume natural for the team.
        $conversationText = $messages->map(function ($msg) {
            $role = $msg->role === 'user' ? 'Customer' : 'Layanan Customer Service';
            return "{$role}: {$msg->content}";
        })->join("\n");

        if (mb_strlen($conversationText) > 6000) {
            $conversationText = '…(awal percakapan dipotong)…'.PHP_EOL.mb_substr($conversationText, -6000);
        }

        // Get model from settings
        $model = Setting::get('default_ai_model', config('services.openrouter.default_model'));

        // Generate summary using OpenRouter. Weak free models occasionally
        // return empty output, safety junk ("User Safety: safe"), or even
        // echo the instruction itself - validate, retry once, and give up
        // cleanly (widget falls back to the generic closing message).
        try {
            $summary = null;

            for ($attempt = 1; $attempt <= 2 && $summary === null; $attempt++) {
                $data = \App\Services\OpenRouterClient::chatCompletion([
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu menulis catatan resume percakapan (ringkasan) untuk tim layanan customer service. '
                                .'Tulis dalam Bahasa Indonesia yang natural, informatif, dan profesional — seperti catatan singkat sesi bantuan yang ditinggalkan untuk tim, '
                                .'maksimal 3 kalimat, mencakup: topik pembicaraan, kebutuhan customer, dan hasil percakapan beserta tindak lanjutnya '
                                .'(misalnya data yang diminta, langkah verifikasi, atau janji follow-up). '
                                .'JANGAN menyebut kata "AI", "chatbot", atau "model" dalam ringkasan — pihak yang melayani disebut "layanan customer service" atau "tim kami". '
                                .'Jangan menyalin label "Customer:"/"Layanan Customer Service:" ke dalam hasil; tulis sebagai prosa yang mengalir.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Buatkan resume dari percakapan berikut:\n\n{$conversationText}"
                        ]
                    ],
                    'temperature' => 0.3,
                    // Free reasoning models burn part of the budget on
                    // reasoning tokens; 250 truncated mid-sentence.
                    'max_tokens' => 400,
                ], 60, 'Cekat SaaS Summary');

                if (!empty($data['error'])) {
                    Log::error('Failed to generate chat summary', [
                        'session_id' => $this->session->id,
                        'attempt' => $attempt,
                        'error' => $data['error'],
                    ]);
                    break;
                }

                $candidate = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
                // Strip accidental markdown code fences.
                if (str_starts_with($candidate, '```')) {
                    $candidate = trim(preg_replace('/^```[a-z]*\n?|\n?```$/i', '', $candidate));
                }

                if ($this->isUsableSummary($candidate)) {
                    $summary = $candidate;
                } else {
                    Log::warning('Chat summary rejected (junk or prompt echo)', [
                        'session_id' => $this->session->id,
                        'attempt' => $attempt,
                        'content' => mb_substr($candidate, 0, 120),
                    ]);
                }
            }

            if ($summary !== null) {
                $this->session->update([
                    'summary' => $summary,
                    'summary_generated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate chat summary', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * A summary must read as Indonesian prose about the conversation.
     * Guards against: empty/short output, safety junk, and weak models
     * that answer by repeating the instructions back ("We need to produce
     * a short summary in Indonesian...", "Buatkan resume dari...").
     */
    protected function isUsableSummary(string $text): bool
    {
        if (mb_strlen($text) < 40) {
            return false;
        }

        // Instruction echo / meta commentary (EN or ID).
        if (preg_match(
            '/we need to produce|short summary|summary (in|of) (indonesian|indonesia)'
            .'|buatkan resume|ringkasan dari percakapan|kamu menulis catatan|maksimal 3 kalimat'
            .'|berikut adalah|berikut ini adalah|as an ai|sebagai ai/i',
            $text
        )) {
            return false;
        }

        // Must contain Indonesian prose markers.
        return (bool) preg_match(
            '/\b(yang|dan|untuk|dengan|pelanggan|customer service|meminta|menjelaskan|'
            .'percakapan|layanan|membantu|menawarkan|menanyakan)\b/i',
            $text
        );
    }
}
