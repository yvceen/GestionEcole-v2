<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'teacher' || Auth::user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'code'               => ['required', 'string', 'max:50', 'unique:courses,code'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'classroom_id'       => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id'         => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id'         => ['required', 'integer', 'exists:users,id'],
            'credits'            => ['nullable', 'integer', 'min:1', 'max:100'],
            'start_date'         => ['required', 'date', 'after:today'],
            'end_date'           => ['required', 'date', 'after:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du cours est obligatoire.',
            'code.required' => 'Le code du cours est obligatoire.',
            'code.unique' => 'Ce code de cours existe déjà.',
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'La classe sélectionnée est invalide.',
            'subject_id.required' => 'La matière est obligatoire.',
            'subject_id.exists' => 'La matière sélectionnée est invalide.',
            'teacher_id.required' => 'L\'enseignant est obligatoire.',
            'teacher_id.exists' => 'L\'enseignant sélectionné est invalide.',
            'start_date.required' => 'La date de début est obligatoire.',
            'start_date.after' => 'La date de début doit être dans le futur.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after' => 'La date de fin doit être après la date de début.',
        ];
    }
}
