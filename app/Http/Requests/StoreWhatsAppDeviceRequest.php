<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhatsAppDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', \App\Models\WhatsAppDevice::class);
    }

    public function rules(): array
    {
        return [
            'device_name' => 'required|string|max:100',
            'phone_number' => 'required|string|regex:/^[0-9]{8,13}$/',
            'widget_id' => [
                'nullable',
                Rule::exists('widgets', 'id')->where('user_id', $this->user()?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'Nomor WhatsApp wajib diisi.',
            'phone_number.regex' => 'Nomor WhatsApp harus berupa 8-13 digit angka.',
        ];
    }
}
