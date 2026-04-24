<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public function rules(): array
    {
        $subject = $this->route('subject');
        $subjectId = is_object($subject) ? $subject->getKey() : $subject;
        $schoolId = $this->resolveSchoolId();

        $nameRule = Rule::unique('subjects', 'name');
        $codeRule = Rule::unique('subjects', 'code');

        if ($schoolId !== null) {
            $nameRule = $nameRule->where(fn ($query) => $query->where('school_id', $schoolId));
            $codeRule = $codeRule->where(fn ($query) => $query->where('school_id', $schoolId));
        }

        if ($subjectId) {
            $nameRule = $nameRule->ignore($subjectId);
            $codeRule = $codeRule->ignore($subjectId);
        }

        return [
            'name' => ['required', 'string', 'max:255', $nameRule],
            'code' => ['nullable', 'string', 'max:50', $codeRule],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la matiere est obligatoire.',
            'name.unique' => 'Cette matiere existe deja.',
            'code.unique' => 'Ce code existe deja.',
        ];
    }

    private function resolveSchoolId(): ?int
    {
        if (app()->bound('current_school_id') && app('current_school_id')) {
            return (int) app('current_school_id');
        }

        return $this->user()?->school_id ? (int) $this->user()->school_id : null;
    }
}
