<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'assessment_id' => ['required', 'integer', 'exists:assessments,id'],
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assessment_id.required' => 'L\'evaluation est obligatoire.',
            'assessment_id.exists' => 'L\'evaluation selectionnee est invalide.',
            'scores.required' => 'Les notes sont obligatoires.',
            'scores.array' => 'Le format des notes est invalide.',
            'scores.*.numeric' => 'Chaque note doit etre un nombre.',
            'scores.*.min' => 'Une note ne peut pas etre negative.',
        ];
    }
}
