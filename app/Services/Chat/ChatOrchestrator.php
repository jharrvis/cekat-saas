<?php

namespace App\Services\Chat;

use App\Events\ChatRequestProcessed;
use App\Events\DomainBlocked;
use App\Events\LeadCaptured;
use App\Events\QuotaExceeded;
use App\Events\WebhookActionTriggered;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Widget;
use App\Support\HttpClientIp;
use App\Support\TextSanitizer;
use App\Support\VisitorGeo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates a single chat turn: widget resolution, domain + quota
 * gates, prompt building, LLM call, webhook actions, persistence.
 *
 * Flow extracted from Api\ChatController::chat. Response shapes are
 * preserved exactly; the controller only maps the result to JSON.
 *
 * @return array{status:int,body:array}
 */
class ChatOrchestrator
{
    /**
     * How many history messages are forwarded to the LLM. Privacy + cost
     * contract: the provider only ever sees a short window, never the
     * whole transcript (client may send more; the server slices here).
     */
    public const HISTORY_WINDOW = 10;

    public function __construct(
        protected DomainAccessService $domains,
        protected QuotaService $quota,
        protected PromptBuilder $prompts,
        protected ModelResolver $models,
        protected LeadCaptureService $leads,
        protected WebhookActionService $webhooks,
        protected SessionIdService $sessions,
    ) {}

    public function handle(string $message, string $widgetSlug, array $history, string $sessionId, string $pageUrl = '', string $referrerUrl = '', ?array $leadForm = null, bool $preview = false): array
    {
        // Captured at request receipt, BEFORE the LLM call (up to ~60s) -
        // otherwise the visitor's message would be stamped with "when the
        // bot finished typing", which made chat times look out of sync.
        $receivedAt = now();
        // Load widget with AI Agent if linked
        $widget = Widget::where('slug', $widgetSlug)
            ->with(['knowledgeBase.faqs', 'user.plan', 'aiAgent.knowledgeBase.faqs'])
            ->first();

        if (! $widget) {
            // Fallback to demo knowledge base from JSON
            $kbPath = storage_path('app/data/knowledge-base.json');
            if (! file_exists($kbPath)) {
                return [
                    'status' => 404,
                    'body' => ['success' => false, 'error' => 'Widget not found', 'error_code' => 'widget_not_found'],
                ];
            }
            $kb = json_decode(file_get_contents($kbPath), true);
        } else {
            // Public visibility gate: only active widgets are served
            if (($widget->status ?? 'active') !== 'active' || ! $widget->is_active) {
                return [
                    'status' => 404,
                    'body' => ['success' => false, 'error' => 'Widget is not active', 'error_code' => 'widget_inactive'],
                ];
            }

            // Domain Validation (Security)
            $origin = request()->header('Origin') ?? request()->header('Referer');
            if (! $this->domains->isAllowed($widget->settings['allowed_domains'] ?? null, $origin)) {
                DomainBlocked::dispatch($widget->slug, $origin);

                return [
                    'status' => 403,
                    'body' => ['success' => false, 'error' => 'Domain not allowed', 'error_code' => 'domain_blocked'],
                ];
            }

            // Check quota before processing (skip for landing page widget).
            // Preview/test chats (T-07) never touch the owner's quota.
            if (! $preview && ($denied = $this->quota->check($widget->user, $widget->slug))) {
                if (($denied['body']['error_code'] ?? null) === 'quota_exceeded' && $widget->user) {
                    QuotaExceeded::dispatch(
                        $widget->user->id,
                        $widget->slug,
                        $denied['body']['quota']['used'],
                        $denied['body']['quota']['limit'],
                    );
                }

                return $denied;
            }

            $kb = $this->prompts->buildKnowledgeArray($widget);

            // A leaked/forged session id from another fingerprint must not
            // continue that conversation - issue a fresh signed id instead.
            $sessionId = $this->sessions->bindFingerprint($widget, $sessionId);
        }

        // Build system prompt
        $systemPrompt = $this->prompts->buildSystemPrompt($kb, $sessionId);

        // The visitor's identity (pre-chat form on turn 1, persisted lead
        // on later turns) must reach the AI - otherwise the bot cannot
        // answer "siapa nama saya?" even though the lead was captured.
        if ($visitor = $this->visitorContext($widget, $sessionId, $leadForm)) {
            $systemPrompt .= "\n".$visitor;
        }

        // Format history (bounded window sent to the provider)
        $formattedHistory = array_slice($history, -self::HISTORY_WINDOW);
        $formattedHistory[] = ['role' => 'user', 'content' => $message];

        // Get model based on user's plan AI tier (LLM Abstraction)
        // AI Agent does NOT determine the model - that's controlled by plan's AI Tier
        $model = $this->models->forWidget($widget);

        // Get temperature from AI Agent if linked, otherwise use default
        $aiAgent = $widget ? $widget->aiAgent : null;
        $temperature = $aiAgent ? $aiAgent->ai_temperature : 0.7;

        // Strategy 2: Trigger System - Insert lead collection prompt based on conditions
        $settings = $kb['settings'] ?? [];
        if ($instruction = $this->leads->triggerInstruction($settings, $message, $this->currentTurn($widget, $settings, $sessionId))) {
            $systemPrompt .= $instruction;
        }

        // Call OpenRouter
        try {
            $result = $this->callOpenRouter($systemPrompt, $formattedHistory, $model, $temperature);
            $model = $result['model'];
            $response = $result['body'];

            // The response body contains the full assistant text - keep it
            // out of production logs (PII would land in laravel.log); full
            // body is only logged while debugging.
            if (config('app.debug')) {
                Log::debug('OpenRouter Response', ['model' => $model, 'response' => $response]);
            } else {
                Log::info('OpenRouter Response', ['model' => $model, 'usage' => $response['usage'] ?? null]);
            }

            $responseText = $response['choices'][0]['message']['content'] ?? null;

            if (! $responseText) {
                // Check if there's an error in response
                if (isset($response['error'])) {
                    Log::error('OpenRouter API Error', ['error' => $response['error']]);
                    throw new \Exception($response['error']['message'] ?? 'API Error');
                }
                throw new \Exception('No response from AI');
            }

                // Handle Webhook Trigger (Function Calling)
                if ($widget) {
                    $replyAction = $this->webhooks->extractAction($responseText);
                    $webhookResult = $this->webhooks->dispatchIfAction($widget, $responseText);

                    if ($webhookResult && $replyAction) {
                        WebhookActionTriggered::dispatch($widget->slug, $replyAction['action']);
                    }

                    // Clean the action JSON from the reply — with OR without a
                    // configured webhook (an unconfigured webhook must not leak
                    // the raw {"action":...} payload into the visitor's chat).
                    if ($replyAction && $this->webhooks->isStrictJson($responseText)) {
                        $responseText = $webhookResult ?? 'Data berhasil diproses.';
                    } elseif ($replyAction) {
                        $cleaned = trim($this->webhooks->stripActionJson($responseText));
                        if ($cleaned !== '') {
                            $responseText = $cleaned;
                        }
                    }

                    // Markdown -> plain text AFTER action extraction (which
                    // needs the raw {"action":...} JSON) and BEFORE persist,
                    // so the widget, chat history, admin inbox and lead email
                    // all show clean text (##, **, raw HTML removed).
                    $responseText = TextSanitizer::markdownToPlain($responseText);

                    $this->persistConversation($widget, $sessionId, $message, $responseText, $model, $response['usage'] ?? [], $pageUrl, $referrerUrl, $receivedAt, $preview);

                    // Lead capture: the pre-chat form (Strategy 3) wins when
                    // the visitor filled it - explicit, complete data over
                    // extraction guesses. Otherwise prefer the AI-emitted
                    // action; when the free model skips the JSON block, fall
                    // back to a deterministic extraction from the visitor's
                    // own message so the lead is never dropped. Fires
                    // regardless of webhook config, after the session row
                    // exists (SendLeadNotification persists the lead onto
                    // the session and emails the widget owner).
                    $formLead = array_filter(
                        array_intersect_key($leadForm ?? [], array_flip(['name', 'email', 'phone'])),
                        fn ($value) => trim((string) $value) !== '',
                    );

                    if ($formLead) {
                        LeadCaptured::dispatch(
                            $widget->slug,
                            array_values(array_intersect(['name', 'email', 'phone'], array_keys($formLead))),
                            $sessionId,
                            array_intersect_key($formLead, array_flip(['name', 'email', 'phone'])),
                        );
                    } else {
                        $action = $replyAction ?? $this->leads->extractLeadFromMessage($message);
                        if ($action && ($action['action'] ?? null) === 'save_lead') {
                            LeadCaptured::dispatch(
                                $widget->slug,
                                array_values(array_intersect(['name', 'email', 'phone'], array_keys($action))),
                                $sessionId,
                                array_intersect_key($action, array_flip(['name', 'email', 'phone'])),
                            );
                        }
                    }

                // Increment user's monthly message quota (skip for landing page widget - unlimited;
                // preview/test chats are free, T-07)
                if (! $preview) {
                    $this->quota->consume($widget->user, $widget->slug);
                }

                ChatRequestProcessed::dispatch(
                    $widget->slug,
                    $widget->user_id,
                    $sessionId,
                    $model,
                    $response['usage']['total_tokens'] ?? 0,
                );
            }

            // Idempotent: covers the widgetless demo path (never persisted);
            // the widget path was already sanitized before persisting.
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'response' => TextSanitizer::markdownToPlain($responseText),
                    'sessionId' => $sessionId,
                    // T-21 (owner masking policy): the response body must never
                    // reveal the model identity or provider-shaped usage data.
                    // Model + tokens stay in server logs and ai_model_used only.
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Chat API Error', [
                'message' => $e->getMessage(),
                'model' => $model,
                'widget' => $widgetSlug,
            ]);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'response' => 'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi dalam beberapa saat.',
                    'sessionId' => $sessionId,
                    'fallback' => true,
                    'error_code' => 'provider_error',
                ],
            ];
        }
    }

    /**
     * Strategy 3's AI context: the visitor's name/email/phone, addressed
     * by name in the reply. Turn 1 carries it only in the request body
     * (the session row does not exist until after the LLM call); later
     * turns read the lead persisted on the session.
     */
    protected function visitorContext(?Widget $widget, string $sessionId, ?array $leadForm): string
    {
        $data = array_filter(
            array_intersect_key($leadForm ?? [], array_flip(['name', 'email', 'phone'])),
            fn ($value) => trim((string) $value) !== '',
        );

        if (! $data && $widget) {
            $session = ChatSession::query()
                ->where('widget_id', $widget->id)
                ->where('visitor_uuid', $sessionId)
                ->first(['visitor_name', 'visitor_email', 'visitor_phone']);

            if ($session) {
                $data = array_filter([
                    'name' => $session->visitor_name,
                    'email' => $session->visitor_email,
                    'phone' => $session->visitor_phone,
                ], fn ($value) => $value !== null && trim((string) $value) !== '');
            }
        }

        if (! $data) {
            return '';
        }

        $parts = [];
        if (isset($data['name'])) {
            $parts[] = 'Nama: '.$data['name'];
        }
        if (isset($data['email'])) {
            $parts[] = 'Email: '.$data['email'];
        }
        if (isset($data['phone'])) {
            $parts[] = 'No HP: '.$data['phone'];
        }

        return '[Data pengunjung: '.implode('; ', $parts)
            .'. Sapa pengunjung dengan namanya dan gunakan data ini bila relevan.]';
    }

    /**
     * Strategy 2's server-side turn counter: how many visitor messages
     * this session already stored + the current one. Never derived from
     * the client's history array (spoofable, and it counts assistant
     * replies too). First message / widgetless demo = turn 1.
     */
    protected function currentTurn(?Widget $widget, array $settings, string $sessionId): int
    {
        if (! $widget || empty($settings['lead_trigger_enabled'])) {
            return 1;
        }

        $session = ChatSession::query()
            ->where('widget_id', $widget->id)
            ->where('visitor_uuid', $sessionId)
            ->first();

        if (! $session) {
            return 1;
        }

        return ChatMessage::query()
                ->where('session_id', $session->id)
                ->where('role', 'user')
                ->count() + 1;
    }

    /**
     * @return array{model:string,body:?array}
     */
    protected function callOpenRouter(string $systemPrompt, array $messages, string $model, float $temperature = 0.7): array
    {
        $allMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$messages,
        ];

        $response = $this->postOpenRouter($allMessages, $model, $temperature);

        // Self-healing: retired model ids (400/404), exhausted free credits
        // (402), rate limits (429) and provider hiccups (5xx) fall back to the
        // Free Models Router once. Auth errors (401/403) do not - a different
        // model would fail the same way.
        if (in_array($response->status(), [400, 402, 404, 429, 500, 502, 503, 504], true) && $model !== ModelResolver::FALLBACK_MODEL) {
            Log::warning('OpenRouter model unavailable, retrying with fallback', [
                'model' => $model,
                'status' => $response->status(),
            ]);

            $model = ModelResolver::FALLBACK_MODEL;
            $response = $this->postOpenRouter($allMessages, $model, $temperature);
        }

        $body = $response->json();
        $content = is_array($body) ? ($body['choices'][0]['message']['content'] ?? null) : 'invalid';

        // Reasoning-heavy free models can spend max_tokens on thinking and
        // return no content - retry once before giving up.
        if (is_array($body) && ! isset($body['error']) && ($content === '' || $content === null)) {
            Log::warning('OpenRouter returned empty content, retrying', ['model' => $model]);

            $response = $this->postOpenRouter($allMessages, $model, $temperature);
            $body = $response->json();
        }

        return ['model' => $model, 'body' => $body];
    }

    protected function postOpenRouter(array $messages, string $model, float $temperature): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Cekat SaaS',
        ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => 1500,
        ]);
    }

    protected function persistConversation(Widget $widget, string $sessionId, string $userMessage, string $aiResponse, string $model, array $usage, string $pageUrl = '', string $referrerUrl = '', ?\DateTimeInterface $receivedAt = null, bool $preview = false): void
    {
        // Full page URL comes from the widget; the Referer header is only an
        // origin-only fallback for older embeds.
        $referer = $referrerUrl !== '' ? $referrerUrl : (string) (request()->header('Referer') ?? '');

        $session = ChatSession::firstOrCreate(
            ['widget_id' => $widget->id, 'visitor_uuid' => $sessionId],
            [
                'current_agent_id' => $widget->ai_agent_id,
                'is_preview' => $preview,
                'started_at' => $receivedAt ?? now(),
                'source_url' => $pageUrl !== '' ? $pageUrl : null,
                'referer_url' => $referer !== '' ? mb_substr($referer, 0, 500) : null,
                'ip_address' => HttpClientIp::get(),
                'user_agent' => request()->userAgent(),
                'device_type' => VisitorGeo::deviceType(request()->userAgent()),
            ]
        );

        // Keep the page/referrer current on every message (a visitor may
        // start on the landing page and continue from another) - this also
        // refreshes updated_at so "Last Activity" reflects real activity.
        $touched = false;
        if ($pageUrl !== '' && $pageUrl !== $session->source_url) {
            $session->source_url = $pageUrl;
            $touched = true;
        }
        if ($referer !== '' && $referer !== $session->referer_url) {
            $session->referer_url = mb_substr($referer, 0, 500);
            $touched = true;
        }
        if ($touched) {
            $session->save();
        }

        if ($session->wasRecentlyCreated) {
            // Coarse geo (country/city) for the chat history detail view.
            // Deferred after the response: the visitor never waits on it,
            // and a lookup failure leaves location_data null (view copes).
            $ip = $session->ip_address;
            $widgetId = $widget->id;
            $uuid = $sessionId;

            app()->terminating(function () use ($ip, $widgetId, $uuid) {
                try {
                    $geo = VisitorGeo::resolve($ip);

                    if ($geo) {
                        ChatSession::where('widget_id', $widgetId)
                            ->where('visitor_uuid', $uuid)
                            ->update(['location_data' => $geo]);
                    }
                } catch (\Throwable $ex) {
                    Log::warning('Visitor geo lookup failed', ['error' => $ex->getMessage()]);
                }
            });
        }

        // The visitor's message is stamped with the receive time, not the
        // reply time; the assistant message keeps the actual reply time.
        $userMessageRow = new ChatMessage([
            'session_id' => $session->id,
            'ai_agent_id' => $widget->ai_agent_id,
            'role' => 'user',
            'content' => $userMessage,
        ]);
        $userMessageRow->created_at = $receivedAt ?? now();
        $userMessageRow->save();

        ChatMessage::create([
            'session_id' => $session->id,
            'ai_agent_id' => $widget->ai_agent_id,
            'role' => 'assistant',
            'content' => $aiResponse,
            'tokens_used' => $usage['total_tokens'] ?? 0,
            'model_used' => $model,
        ]);
    }
}
