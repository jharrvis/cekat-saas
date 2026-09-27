<?php

namespace App\Services\Chat;

/**
 * Strategy 2 (trigger system) of lead collection: decides whether the
 * assistant should ask for the visitor's contact details after answering.
 *
 * Extracted from Api\ChatController. Returns the instruction to append
 * to the system prompt, or null when no trigger fires.
 */
class LeadCaptureService
{
    public function triggerInstruction(array $settings, array $history, string $message): ?string
    {
        if (empty($settings['lead_trigger_enabled'])) {
            return null;
        }

        $triggerAfter = $settings['lead_trigger_after_message'] ?? 3;
        $triggerKeywords = $settings['lead_trigger_keywords'] ?? '';
        $keywordList = array_map('trim', explode(',', strtolower($triggerKeywords)));

        $messageCount = count($history) + 1; // Including current message
        $messageLower = strtolower($message);

        $shouldTrigger = false;

        if ($messageCount >= $triggerAfter) {
            $shouldTrigger = true;
        }

        foreach ($keywordList as $keyword) {
            if (! empty($keyword) && strpos($messageLower, $keyword) !== false) {
                $shouldTrigger = true;
                break;
            }
        }

        if (! $shouldTrigger) {
            return null;
        }

        return "\n[PENTING: Setelah menjawab pertanyaan ini, tanyakan nama/email/telepon user dengan sopan]";
    }
}
