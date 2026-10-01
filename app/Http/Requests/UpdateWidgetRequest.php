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
    }

    public function rules(): array
    {
        return [
            'tab' => 'nullable|string',
            // General tab
            'display_name' => 'nullable|max:255',
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
            // Webhook tab
            'webhook_url' => 'nullable|url|max:2000',
            'webhook_secret' => 'nullable|string|max:500',
        ];
    }
}
