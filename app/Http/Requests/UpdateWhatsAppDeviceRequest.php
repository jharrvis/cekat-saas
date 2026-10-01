<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWhatsAppDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('update', $this->route('device'));
    }

    public function rules(): array
    {
        return [
            'device_name' => 'sometimes|string|max:100',
            'widget_id' => [
                'nullable',
                Rule::exists('widgets', 'id')->where('user_id', $this->user()?->id),
            ],
            'is_active' => 'sometimes|boolean',
        ];
    }
}
