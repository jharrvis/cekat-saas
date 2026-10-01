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
