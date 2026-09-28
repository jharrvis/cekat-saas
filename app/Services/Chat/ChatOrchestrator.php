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

    public function handle(string $message, string $widgetSlug, array $history, string $sessionId): array
    {
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

            // Check quota before processing (skip for landing page widget)
            if ($denied = $this->quota->check($widget->user, $widget->slug)) {
                if (($denied['body']['error_code'] ?? null) === 'quota_exceeded' && $widget->user) {
                    QuotaExceeded::dispatch(
                        $widget->user->id,
                        $widget->slug,
                        $widget->user->monthly_message_used,
                        $widget->user->plan->max_messages_per_month,
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
        if ($instruction = $this->leads->triggerInstruction($settings, $history, $message)) {
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
                $action = $this->webhooks->extractAction($responseText);
                $webhookResult = $this->webhooks->dispatchIfAction($widget, $responseText);

                if ($webhookResult && $action) {
                    WebhookActionTriggered::dispatch($widget->slug, $action['action']);

                    if ($action['action'] === 'save_lead') {
                        LeadCaptured::dispatch(
                            $widget->slug,
                            array_values(array_intersect(['name', 'email', 'phone'], array_keys($action))),
                        );
                    }
                }

                // If strictly JSON, replace with friendly message
                if ($this->webhooks->isStrictJson($responseText) && $webhookResult) {
                    $responseText = $webhookResult;
                }

                // Clean JSON from response if mixed
                if ($webhookResult && ! $this->webhooks->isStrictJson($responseText)) {
                    $responseText = $this->webhooks->stripActionJson($responseText);
                }

                $this->persistConversation($widget, $sessionId, $message, $responseText, $model, $response['usage'] ?? []);

                // Increment user's monthly message quota (skip for landing page widget - unlimited)
                $this->quota->consume($widget->user, $widget->slug);

                ChatRequestProcessed::dispatch(
                    $widget->slug,
                    $widget->user_id,
                    $sessionId,
                    $model,
                    $response['usage']['total_tokens'] ?? 0,
                );
            }

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'response' => $responseText,
                    'sessionId' => $sessionId,
                    'usage' => $response['usage'] ?? null,
                    'meta' => [
                        'model' => $model,
                        'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                    ],
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

    protected function persistConversation(Widget $widget, string $sessionId, string $userMessage, string $aiResponse, string $model, array $usage): void
    {
        $session = ChatSession::firstOrCreate(
            ['widget_id' => $widget->id, 'visitor_uuid' => $sessionId],
            [
                'current_agent_id' => $widget->ai_agent_id,
                'started_at' => now(),
                'ip_address' => HttpClientIp::get(),
                'user_agent' => request()->userAgent(),
            ]
        );

        ChatMessage::create([
            'session_id' => $session->id,
            'ai_agent_id' => $widget->ai_agent_id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

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
