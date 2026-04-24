<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', 'unique:classrooms,name'],
            'level_id'   => ['required', 'integer', 'exists:levels,id'],
            'capacity'   => ['required', 'integer', 'min:1', 'max:100'],
            'location'   => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la classe est obligatoire.',
            'name.unique' => 'Cette classe existe déjà.',
            'level_id.required' => 'Le niveau est obligatoire.',
            'level_id.exists' => 'Le niveau sélectionné est invalide.',
            'capacity.required' => 'La capacité est obligatoire.',
            'capacity.min' => 'La capacité doit être au moins 1.',
        ];
    }
}
