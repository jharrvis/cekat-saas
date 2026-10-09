<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Store the account WhatsApp number in the canonical 62xx digit
        // form whenever it parses as an Indonesian number.
        $raw = trim((string) $this->input('whatsapp_number', ''));

        if ($raw !== '') {
            $this->merge([
                'whatsapp_number' => \App\Services\WhatsApp\PhoneNumber::normalizeId($raw) ?? $raw,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // T-12: language preference; only locales that ship a lang/
            // folder are accepted (list resolved by the SetLocale middleware).
            'locale' => ['nullable', 'string', \Illuminate\Validation\Rule::in(\App\Http\Middleware\SetLocale::availableLocales())],
            // Account-level fallback destination for WhatsApp notifications.
            'whatsapp_number' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\s.\-]+$/'],
        ];
    }
}
