<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! Hash::check($this->input('current_password'), $this->user()->password)) {
                $validator->errors()->add('current_password', 'Password saat ini salah');
            }
        });
    }
}
