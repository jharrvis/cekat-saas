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

    /**
     * Deterministic fallback for lead capture: free models do not always
     * emit the save_lead JSON block the prompt asks for. When the visitor
     * writes contact details straight into the chat, extract them here so
     * the lead is never silently dropped.
     *
     * Returns a save_lead action array, or null when the message carries
     * no contact info (a bare name without email/phone is not a lead).
     */
    public function extractLeadFromMessage(string $message): ?array
    {
        $lead = [];

        if (preg_match('/[\w.+-]+@[\w-]+\.[\w.]{2,}/u', $message, $m)) {
            $lead['email'] = rtrim(strtolower($m[0]), '.,;');
        }

        if (preg_match('/(?:\+?62|0)8[1-9][0-9]{6,11}\b/u', $message, $m)) {
            $lead['phone'] = ltrim($m[0], '+');
        }

        if (preg_match('/\bnama\s+(?:saya|aku|gue)\s*(?:adalah\s+|:\s*)?([^,.;\n]{2,60})/iu', $message, $m)) {
            $name = preg_replace('/\s+/', ' ', trim($m[1]));
            // stop the capture at the next contact keyword ("... email", "HP")
            $name = trim(preg_replace('/\s+(?:email|e-?mail|telp|telepon|hp|no\.?|nomor|alamat)\b.*$/iu', '', $name));
            if ($name !== '') {
                $lead['name'] = $name;
            }
        }

        if (! isset($lead['email']) && ! isset($lead['phone'])) {
            return null;
        }

        return array_merge(['action' => 'save_lead'], $lead);
    }
}
