<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public function rules(): array
    {
        $schoolId = $this->currentSchoolId();
        $vehicleRule = Rule::exists('vehicles', 'id');

        if ($schoolId !== null) {
            $vehicleRule = $vehicleRule->where(fn ($query) => $query->where('school_id', $schoolId));
        }

        return [
            'route_name' => ['required', 'string', 'max:255'],
            'vehicle_id' => ['nullable', 'integer', $vehicleRule],
            'start_point' => ['required', 'string', 'max:255'],
            'end_point' => ['required', 'string', 'max:255'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'stops' => ['required', 'json'],
        ];
    }

    public function messages(): array
    {
        return [
            'route_name.required' => 'Le nom de la route est obligatoire.',
            'start_point.required' => 'Le point de depart est obligatoire.',
            'end_point.required' => 'Le point d arrivee est obligatoire.',
            'monthly_fee.required' => 'Le tarif mensuel est obligatoire.',
            'monthly_fee.numeric' => 'Le tarif mensuel doit etre un nombre.',
            'vehicle_id.exists' => 'Le vehicule selectionne est invalide pour cette ecole.',
            'distance_km.numeric' => 'La distance doit etre un nombre.',
        ];
    }

    private function currentSchoolId(): ?int
    {
        if (app()->bound('current_school_id') && app('current_school_id')) {
            return (int) app('current_school_id');
        }

        return Auth::user()?->school_id ? (int) Auth::user()->school_id : null;
    }
}
