<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled in controller (admin check)
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'La raison ne doit pas dépasser 500 caractères.',
        ];
    }
}
