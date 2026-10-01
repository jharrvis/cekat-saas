<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', \App\Models\Widget::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('ai_agent_id') === '') {
            $this->merge(['ai_agent_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'display_name' => 'required|max:255',
            'description' => 'nullable|max:500',
            // The linked agent must belong to the requesting user.
            'ai_agent_id' => [
                'nullable',
                Rule::exists('ai_agents', 'id')->where('user_id', $this->user()?->id),
            ],
        ];
    }
}
