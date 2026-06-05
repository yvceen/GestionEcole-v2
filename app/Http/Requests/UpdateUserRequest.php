<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->user()->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', "unique:users,email,{$userId}"],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', 'in:admin,director,teacher,parent,student,chauffeur,school_life,accueil'],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Le nom est obligatoire.',
            'email.required'       => 'L\'email est obligatoire.',
            'email.email'          => 'L\'email doit etre une adresse valide.',
            'email.unique'         => 'Cet email est deja utilise.',
            'role.required'        => 'Le role est obligatoire.',
            'role.in'              => 'Le role selectionne est invalide.',
        ];
    }
}
