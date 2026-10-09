<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership is enforced by the controller's scoped lookup;
        // the linked agent must additionally belong to the user.
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // The "no agent" option submits an empty string; normalize to null.
        if ($this->input('ai_agent_id') === '') {
            $this->merge(['ai_agent_id' => null]);
        }

        // The lead-notification email input stays in the form while its
        // panel is hidden (toggle off) and submits ""; drop the empty key
        // so the non-implicit `email` rule does not fail on it. When the
        // toggle IS on, an empty value must keep failing required_if.
        if (! $this->has('lead_email_notif_enabled') && trim((string) $this->input('lead_email_notif', '')) === '') {
            $this->request->remove('lead_email_notif');
        }

        // Same pattern for the WhatsApp notification number: the input
        // submits "" while its toggle is off; drop it so the format rule
        // does not fail on the empty hidden value.
        if (! $this->has('lead_wa_notif_enabled') && trim((string) $this->input('lead_wa_notif', '')) === '') {
            $this->request->remove('lead_wa_notif');
        }
    }

    public function rules(): array
    {
        return [
            'tab' => 'nullable|string',
            // General tab
            // The channel name is mandatory on the general tab (T-06); other
            // tabs do not submit it and keep it optional. Mirrors the
            // controller's tab default ('general' when absent).
            'display_name' => [Rule::requiredIf(fn () => $this->input('tab', 'general') === 'general'), 'string', 'max:255'],
            'description' => 'nullable|max:500',
            'allowed_domains' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'ai_agent_id' => [
                'nullable',
                Rule::exists('ai_agents', 'id')->where('user_id', $this->user()?->id),
            ],
            // Model tab
            'settings' => 'nullable|array',
            'settings.model' => 'nullable|string|max:255',
            // Lead tab
            'lead_trigger_after_message' => 'nullable|integer|min:1|max:50',
            'lead_trigger_keywords' => 'nullable|string|max:1000',
            'lead_email_notif' => ['required_if:lead_email_notif_enabled,1', 'email', 'max:255'],
            // Optional even when the toggle is on: an empty channel
            // number falls back to the account notification number.
            'lead_wa_notif' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\s.\-]+$/'],
            // Webhook tab
            'webhook_url' => 'nullable|url|max:2000',
            'webhook_secret' => 'nullable|string|max:500',
        ];
    }
}
