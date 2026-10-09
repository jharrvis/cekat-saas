<?php

namespace App\Services\Chat;

use App\Models\Widget;
use App\Services\WebhookService;

/**
 * Handles AI-emitted JSON actions (save_lead, check_status, create_order):
 * extracts the action block, forwards it to the widget webhook, and
 * cleans the JSON out of mixed user-facing replies.
 *
 * Extracted from Api\ChatController::handleWebhookTrigger.
 */
class WebhookActionService
{
    public function __construct(protected WebhookService $webhookService) {}

    /**
     * Decode the first JSON action block in the assistant reply, if any.
     */
    public function extractAction(string $responseText): ?array
    {
        if (! preg_match('/\{[\s\S]*\}/', $responseText, $matches)) {
            return null;
        }

        $data = json_decode($matches[0], true);

        return is_array($data) && isset($data['action']) ? $data : null;
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
     * Remove the JSON action block from a mixed reply.
     */
    public function stripActionJson(string $responseText): string
    {
        return preg_replace('/\{[\s\S]*\}/', '', $responseText);
    }

    public function isStrictJson(string $responseText): bool
    {
        $trimmed = trim($responseText);

        return $trimmed !== '' && $trimmed[0] === '{';
    }
}
