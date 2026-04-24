<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->currentSchoolId();

        $classroomRule = Rule::exists('classrooms', 'id');
        $userRule = Rule::exists('users', 'id');

        if ($schoolId !== null) {
            $classroomRule = $classroomRule->where(fn ($query) => $query->where('school_id', $schoolId));
            $userRule = $userRule->where(fn ($query) => $query->where('school_id', $schoolId));
        }

        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'reply_to_id' => ['nullable', 'integer', Rule::exists('messages', 'id')],
            'classroom_id' => ['nullable', 'integer', $classroomRule],
            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer', $userRule],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', $userRule],
            'recipient_id' => ['nullable', 'integer', $userRule],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Le message est obligatoire.',
            'body.min' => 'Le message doit contenir au moins 5 caracteres.',
            'body.max' => 'Le message ne doit pas depasser 5000 caracteres.',
            'subject.max' => 'Le sujet ne doit pas depasser 255 caracteres.',
            'reply_to_id.exists' => 'La conversation selectionnee est invalide.',
            'classroom_id.exists' => 'La classe selectionnee est invalide pour cette ecole.',
            'parent_ids.*.exists' => 'Un ou plusieurs parents sont invalides pour cette ecole.',
            'teacher_ids.*.exists' => 'Un ou plusieurs enseignants sont invalides pour cette ecole.',
            'recipient_id.exists' => 'Le destinataire selectionne est invalide pour cette ecole.',
        ];
    }

    private function currentSchoolId(): ?int
    {
        if (app()->bound('current_school_id') && app('current_school_id')) {
            return (int) app('current_school_id');
        }

        return $this->user()?->school_id ? (int) $this->user()->school_id : null;
    }
}
