<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('update', $this->route('agent'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'personality' => 'required|in:professional,friendly,casual,formal',
            'ai_temperature' => 'required|numeric|min:0|max:2',
            'system_prompt' => 'nullable|string|max:2000',
            'fallback_message' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }
}
