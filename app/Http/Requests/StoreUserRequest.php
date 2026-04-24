<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', 'in:admin,director,teacher,parent,student,chauffeur,school_life'],
            'password' => ['required',  Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Le nom est obligatoire.',
            'email.required'       => 'L\'email est obligatoire.',
            'email.email'          => 'L\'email doit être une adresse valide.',
            'email.unique'         => 'Cet email est déjà utilisé.',
            'role.required'        => 'Le rôle est obligatoire.',
            'role.in'              => 'Le rôle sélectionné est invalide.',
            'password.required'    => 'Le mot de passe est obligatoire.',
            'password.min'         => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
