<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['required', 'integer', 'exists:users,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'string', 'distinct', 'date_format:Y-m'],
            'method' => ['required', 'string', 'in:cash,transfer,card,check'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => 'Selectionnez un parent.',
            'parent_id.exists' => 'Le parent selectionne est invalide.',
            'student_ids.required' => 'Selectionnez au moins un eleve.',
            'student_ids.min' => 'Selectionnez au moins un eleve.',
            'student_ids.*.exists' => 'Un eleve selectionne est invalide.',
            'months.required' => 'Selectionnez au moins un mois.',
            'months.min' => 'Selectionnez au moins un mois.',
            'months.*.date_format' => 'Chaque mois doit etre au format AAAA-MM.',
            'method.required' => 'Selectionnez une methode de paiement.',
            'method.in' => 'La methode de paiement choisie est invalide.',
            'paid_at.date' => 'La date de paiement est invalide.',
            'note.max' => 'La note ne peut pas depasser 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => 'parent',
            'student_ids' => 'eleves',
            'months' => 'mois',
            'method' => 'methode',
            'paid_at' => 'date de paiement',
            'note' => 'note',
        ];
    }
}
