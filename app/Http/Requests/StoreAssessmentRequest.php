<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'max:100'],
            'coefficient' => ['nullable', 'numeric', 'min:0.25', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'La classe selectionnee est invalide.',
            'subject_id.required' => 'La matiere est obligatoire.',
            'subject_id.exists' => 'La matiere selectionnee est invalide.',
            'title.required' => 'Le titre de l\'evaluation est obligatoire.',
            'date.required' => 'La date de l\'evaluation est obligatoire.',
            'max_score.required' => 'La note maximale est obligatoire.',
            'max_score.integer' => 'La note maximale doit etre un entier.',
        ];
    }
}
