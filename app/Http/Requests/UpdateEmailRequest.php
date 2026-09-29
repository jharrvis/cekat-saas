<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::notIn([$this->user()?->email]),
                Rule::unique('users', 'email'),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'email baru'];
    }
}
