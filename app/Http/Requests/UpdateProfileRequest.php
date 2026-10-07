<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // T-12: language preference; only locales that ship a lang/
            // folder are accepted (list resolved by the SetLocale middleware).
            'locale' => ['nullable', 'string', \Illuminate\Validation\Rule::in(\App\Http\Middleware\SetLocale::availableLocales())],
        ];
    }
}
