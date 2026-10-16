<?php

namespace App\Services\Chat;

use App\Models\Widget;
use App\Services\WebhookService;

/**
 * Handles AI-emitted actions (save_lead, check_status, create_order, api_lookup):
 * extracts the action block, forwards it to the widget webhook (or the
 * synchronous lookup dispatcher), and cleans the block out of mixed
 * user-facing replies.
 *
 * Besides the instructed {"action": ...} JSON, also understands native
 * <tool_call_start>[name(k=v, ...)]<tool_call_end> transcripts emitted by
 * models that ignore the JSON instruction.
 *
 * Extracted from Api\ChatController::handleWebhookTrigger.
 */
class WebhookActionService
{
    public function __construct(protected WebhookService $webhookService) {}

    /**
     * Decode the first JSON action block in the assistant reply, if any.
     *
     * Also understands the native tool-call transcript some models emit
     * instead of the instructed {"action": ...} JSON (observed with Gemini),
     * e.g.:
     *
     *   <tool_call_start>
     *   [api_lookup(action='api_lookup', lookup='jadwal_sidang_perkara',
     *               params={'nomor': '51/Pdt.P/2026/PA.Sal'})]
     *   <tool_call_end>
     *
     * The block is normalized to the same action array shape as the JSON
     * path so the rest of the pipeline (dispatch, cleaning) works unchanged.
     */
    public function extractAction(string $responseText): ?array
    {
        $toolCall = $this->parseToolCallBlock($responseText);
        if ($toolCall !== null) {
            return $toolCall;
        }

        if (! preg_match('/\{[\s\S]*\}/', $responseText, $matches)) {
            return null;
        }

        $data = json_decode($matches[0], true);

        return is_array($data) && isset($data['action']) ? $data : null;
    }

    /**
     * Parse a <tool_call_start>[name(k=v, ...)]<tool_call_end> block into a
     * normalized action array. Returns null when no such block exists or it
     * cannot be parsed.
     */
    private function parseToolCallBlock(string $text): ?array
    {
        if (! preg_match('/<\|?tool_call_start\|?>\s*\[([A-Za-z_][A-Za-z0-9_]*)\(/s', $text, $m, PREG_OFFSET_CAPTURE)) {
            // Fallback: blok tanpa tag pembungkus, mis. [api_lookup(...)] mentah.
            if (! preg_match('/\[((?:api_lookup|check_status|create_order))\s*\(/s', $text, $m, PREG_OFFSET_CAPTURE)) {
                return null;
            }
        }

        $func = $m[1][0];
        // Position of the '(' opening the argument list.
        $openPos = $m[0][1] + strlen($m[0][0]) - 1;
        $argsStr = $this->extractBalanced($text, $openPos);
        if ($argsStr === null) {
            return null;
        }

        $args = $this->parsePyKwargs($argsStr);
        if ($args === null) {
            return null;
        }

        $action = $args['action'] ?? $func;
        if (! is_string($action) || $action === '') {
            return null;
        }
        $args['action'] = $action;

        return $args;
    }

    /**
     * Extract the balanced (...) substring starting at $openPos (which must
     * point at the opening paren). Handles nested (), {}, [] and quoted
     * strings with backslash escapes. Returns the inner text, or null when
     * the parens never balance.
     */
    private function extractBalanced(string $text, int $openPos): ?string
    {
        $pairs = ['(' => ')', '{' => '}', '[' => ']'];
        $len = strlen($text);
        $stack = [];
        $quote = null;

        for ($i = $openPos; $i < $len; $i++) {
            $c = $text[$i];

            if ($quote !== null) {
                if ($c === '\\') {
                    $i++; // skip escaped char
                } elseif ($c === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($c === "'" || $c === '"') {
                $quote = $c;
                continue;
            }

            if (isset($pairs[$c])) {
                $stack[] = $pairs[$c];
                continue;
            }

            if ($c === ')' || $c === '}' || $c === ']') {
                $expected = array_pop($stack);
                if ($expected === null || $expected !== $c) {
                    return null; // unbalanced
                }
                if ($stack === []) {
                    return substr($text, $openPos + 1, $i - $openPos - 1);
                }
            }
        }

        return null;
    }

    /**
     * Parse Python-style keyword arguments: name=value, name=value, ...
     * Only top-level commas split (commas inside quotes/brackets are kept).
     */
    private function parsePyKwargs(string $s): ?array
    {
        $out = [];
        foreach ($this->splitTopLevel($s, ',') as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (! preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=(.*)$/s', $part, $m)) {
                return null;
            }
            $out[$m[1]] = $this->parsePyValue(trim($m[2]));
        }

        return $out;
    }

    /**
     * Split on a delimiter, ignoring delimiters inside quotes or brackets.
     *
     * @return string[]
     */
    private function splitTopLevel(string $s, string $delim): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];

            if ($quote !== null) {
                $current .= $c;
                if ($c === '\\' && $i + 1 < $len) {
                    $current .= $s[++$i];
                } elseif ($c === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($c === "'" || $c === '"') {
                $quote = $c;
                $current .= $c;
                continue;
            }

            if ($c === '(' || $c === '{' || $c === '[') {
                $depth++;
            } elseif ($c === ')' || $c === '}' || $c === ']') {
                $depth = max(0, $depth - 1);
            }

            if ($c === $delim && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $c;
        }

        $parts[] = $current;

        return $parts;
    }

    /**
     * Parse a Python-literal-ish value: 'string', "string", {...}, [...],
     * numbers, True/False/None. Anything else is returned as-is (string).
     */
    private function parsePyValue(string $v): mixed
    {
        if ($v === '') {
            return '';
        }

        $first = $v[0];
        $last = substr($v, -1);

        // Quoted string.
        if (($first === "'" || $first === '"') && $last === $first && strlen($v) >= 2) {
            return $this->unescapePyString(substr($v, 1, -1));
        }

        // Dict.
        if ($first === '{' && $last === '}') {
            $out = [];
            foreach ($this->splitTopLevel(substr($v, 1, -1), ',') as $pair) {
                $pair = trim($pair);
                if ($pair === '') {
                    continue;
                }
                $kv = $this->splitTopLevel($pair, ':');
                if (count($kv) !== 2) {
                    continue;
                }
                $key = $this->parsePyValue(trim($kv[0]));
                $key = is_scalar($key) ? (string) $key : json_encode($key);
                $out[$key] = $this->parsePyValue(trim($kv[1]));
            }

            return $out;
        }

        // List.
        if ($first === '[' && $last === ']') {
            $out = [];
            foreach ($this->splitTopLevel(substr($v, 1, -1), ',') as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }
                $out[] = $this->parsePyValue($item);
            }

            return $out;
        }

        // Parenthesized group: model kadang menulis dict pakai (...) bukan {...}.
        // Jika isi mengandung ':' top-level -> dict, selain itu -> list.
        if ($first === '(' && $last === ')') {
            $inner = substr($v, 1, -1);
            $hasColon = false;
            foreach ($this->splitTopLevel($inner, ',') as $chk) {
                if (count($this->splitTopLevel(trim($chk), ':')) === 2) { $hasColon = true; break; }
            }
            if ($hasColon) {
                return $this->parsePyValue('{'.$inner.'}');
            }
            return $this->parsePyValue('['.$inner.']');
        }
        $lower = strtolower($v);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        if ($lower === 'none' || $lower === 'null') {
            return null;
        }
        if (is_numeric($v)) {
            return str_contains($v, '.') ? (float) $v : (int) $v;
        }

        return $v;
    }

    /**
     * Unescape a Python single/double-quoted string body.
     */
    private function unescapePyString(string $body): string
    {
        return preg_replace_callback(
            '/\\\\(.)/s',
            static fn (array $m): string => match ($m[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                '\\' => '\\',
                "'" => "'",
                '"' => '"',
                default => $m[1],
            },
            $body
        );
    }

    /**
     * Dispatch the action to the widget webhook when configured.
     *
     * @return string|null Friendly replacement message when the assistant
     *                     reply was strictly JSON and an action was sent.
     */
    public function dispatchIfAction(Widget $widget, string $responseText): ?string
    {
        $data = $this->extractAction($responseText);

        if (! $data) {
            return null;
        }

        $webhookUrl = $widget->settings['webhook_url'] ?? null;
        $webhookSecret = $widget->settings['webhook_secret'] ?? '';

        if (! $webhookUrl) {
            return null;
        }

        // Add widget info to payload
        $data['widget_id'] = $widget->slug;
        $data['customer_id'] = $widget->user_id;

        $this->webhookService->send($webhookUrl, $data, $webhookSecret);

        return 'Data berhasil diproses.';
    }

    /**
     * Synchronous order-status lookup (Fase B): forward a check_status
     * action to the widget webhook and turn the store's answer into the
     * visitor-facing reply, in the widget owner's locale.
     *
     * The store (WordPress plugin) verifies ownership against the
     * visitor's contact: the email/phone from the action payload or the
     * chat session's captured lead data. Returns null when the lookup
     * cannot run (no webhook configured, transport failure) so the
     * caller falls back to the generic fire-and-forget flow.
     */
    public function dispatchCheckStatus(Widget $widget, array $action, ?string $contactEmail, ?string $contactPhone): ?string
    {
        $referenceId = trim((string) ($action['reference_id'] ?? ''));

        $webhookUrl = $widget->settings['webhook_url'] ?? null;

        if ($referenceId === '' || ! $webhookUrl) {
            return null;
        }

        $payload = [
            'action' => 'check_status',
            'reference_id' => $referenceId,
            'email' => $contactEmail ?: ($action['email'] ?? null),
            'phone' => $contactPhone ?: ($action['phone'] ?? null),
            'widget_id' => $widget->slug,
            'customer_id' => $widget->user_id,
        ];

        $result = $this->webhookService->send($webhookUrl, $payload, $widget->settings['webhook_secret'] ?? '');

        if (! ($result['success'] ?? false) || ! is_array($result['body'] ?? null)) {
            return null;
        }

        $locale = $widget->user?->locale ?: config('app.locale');
        $body = $result['body'];

        if (($body['success'] ?? false) && is_array($body['order'] ?? null)) {
            return $this->composeOrderReply($body['order'], $locale);
        }

        return match ($body['error'] ?? null) {
            'verification_required' => __('chat.order_verify_ask', [], $locale),
            'verification_failed', 'order_not_found' => __('chat.order_lookup_failed', [], $locale),
            default => null,
        };
    }

    /**
     * Synchronous order creation (Fase C): forward a create_order action
     * to the store webhook and turn the result into the visitor-facing
     * reply, in the widget owner's locale.
     *
     * Two safeguards before anything reaches the store:
     * - Buyer gate: without a name and at least one contact (email or
     *   phone) there is nobody to attach the order to - ask instead of
     *   calling. Contact values merge like check_status (action payload,
     *   pre-chat form, session lead data - resolved by the caller).
     * - Duplicate guard: $dedupeKey claims the creation for a short
     *   window; a repeated identical submission inside it replays the
     *   first composed reply instead of creating a second order.
     *
     * Returns null on transport failure so the caller falls back to the
     * generic fire-and-forget flow.
     */
    public function dispatchCreateOrder(Widget $widget, array $action, ?string $buyerName, ?string $buyerEmail, ?string $buyerPhone, ?string $dedupeKey = null): ?string
    {
        $webhookUrl = $widget->settings['webhook_url'] ?? null;
        $items = $action['items'] ?? null;

        if (! $webhookUrl || ! is_array($items) || $items === []) {
            return null;
        }

        $locale = $widget->user?->locale ?: config('app.locale');

        $name = trim((string) ($buyerName ?: ($action['name'] ?? '')));
        $email = trim((string) ($buyerEmail ?: ($action['email'] ?? '')));
        $phone = trim((string) ($buyerPhone ?: ($action['phone'] ?? '')));

        if ($name === '' || ($email === '' && $phone === '')) {
            return __('chat.order_create_need_contact', [], $locale);
        }

        if ($dedupeKey && $cached = \Illuminate\Support\Facades\Cache::get($dedupeKey)) {
            return $cached;
        }

        $payload = [
            'action' => 'create_order',
            'items' => $items,
            'customer' => [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => trim((string) ($action['address'] ?? '')),
            ],
            'notes' => trim((string) ($action['notes'] ?? '')),
            'widget_id' => $widget->slug,
            'customer_id' => $widget->user_id,
        ];

        $result = $this->webhookService->send($webhookUrl, $payload, $widget->settings['webhook_secret'] ?? '');

        if (! ($result['success'] ?? false) || ! is_array($result['body'] ?? null)) {
            return null;
        }

        $body = $result['body'];

        if (($body['success'] ?? false) && is_array($body['order'] ?? null)) {
            $order = $body['order'];
            $lines = [
                __('chat.order_created_title', ['id' => $order['number'] ?? $order['id'] ?? '-'], $locale),
            ];

            if (! empty($order['total'])) {
                $lines[] = __('chat.order_total_line', ['total' => $order['total']], $locale);
            }

            if (! empty($order['payment_url'])) {
                $lines[] = __('chat.order_created_pay', ['url' => $order['payment_url']], $locale);
            }

            $reply = implode("\n", $lines);

            if ($dedupeKey) {
                \Illuminate\Support\Facades\Cache::put($dedupeKey, $reply, now()->addMinutes(10));
            }

            return $reply;
        }

        return match ($body['error'] ?? null) {
            'product_unavailable' => __('chat.order_create_unavailable', ['detail' => $body['detail'] ?? ''], $locale),
            'product_needs_variant' => __('chat.order_create_needs_variant', ['detail' => $body['detail'] ?? ''], $locale),
            default => null,
        };
    }

    /**
     * Render the store's order payload as plain chat text. All money
     * values arrive pre-formatted from the store; status labels are
     * localized here (unknown slugs pass through untouched).
     */
    private function composeOrderReply(array $order, ?string $locale): string
    {
        $lines = [
            __('chat.order_status_title', ['id' => $order['number'] ?? $order['id'] ?? '-'], $locale),
        ];

        if (! empty($order['status'])) {
            $slug = (string) $order['status'];
            $label = __("chat.order_status_labels.{$slug}", [], $locale);
            $lines[] = __('chat.order_status_line', ['status' => $label === "chat.order_status_labels.{$slug}" ? $slug : $label], $locale);
        }

        if (! empty($order['date'])) {
            $lines[] = __('chat.order_date_line', ['date' => $order['date']], $locale);
        }

        if (! empty($order['items']) && is_array($order['items'])) {
            $lines[] = __('chat.order_items_title', [], $locale);

            foreach ($order['items'] as $item) {
                $lines[] = __('chat.order_item_line', [
                    'qty' => $item['qty'] ?? 1,
                    'name' => $item['name'] ?? '-',
                    'total' => $item['total'] ?? '',
                ], $locale);
            }
        }

        if (! empty($order['total'])) {
            $lines[] = __('chat.order_total_line', ['total' => $order['total']], $locale);
        }

        if (! empty($order['payment_method'])) {
            $lines[] = __('chat.order_payment_line', [
                'method' => $order['payment_method'],
                'state' => __(($order['paid'] ?? false) ? 'chat.order_paid' : 'chat.order_unpaid', [], $locale),
            ], $locale);
        }

        if (! empty($order['tracking'])) {
            $lines[] = __('chat.order_tracking_line', ['tracking' => $order['tracking']], $locale);
        }

        return implode("\n", $lines);
    }

    /**
     * Remove the JSON action block (or a <tool_call_start> block) from a
     * mixed reply.
     */
    public function stripActionJson(string $responseText): string
    {
        $text = preg_replace('/<\|?tool_call_start\|?>[\s\S]*?<\|?tool_call_end\|?>/', '', $responseText);
        $text = $text ?? $responseText;
        // Fail-safe: jangan pernah tampilkan sintaks tool-call mentah ke pengunjung,
        // termasuk varian tanpa tag pembungkus.
        $text = preg_replace('/\[(?:api_lookup|check_status|create_order)\s*\([\s\S]*?\)\]/', '', $text);

        return preg_replace('/\{[\s\S]*\}/', '', $text);
    }

    public function isStrictJson(string $responseText): bool
    {
        $trimmed = trim($responseText);

        if ($trimmed === '') {
            return false;
        }

        if ($trimmed[0] === '{') {
            return true;
        }

        // A reply consisting solely of a tool-call block counts as strict too.
        return (bool) preg_match('/^<\|?tool_call_start\|?>[\s\S]*<\|?tool_call_end\|?>$/', $trimmed);
    }
}
