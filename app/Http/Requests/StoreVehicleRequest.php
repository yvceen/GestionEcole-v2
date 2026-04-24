<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;
        $schoolId = $this->currentSchoolId();

        $driverRule = Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'chauffeur'));
        if ($schoolId !== null) {
            $driverRule = $driverRule->where(fn ($query) => $query->where('school_id', $schoolId)->where('role', 'chauffeur'));
        }

        return [
            'name' => ['nullable', 'string', 'max:120'],
            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'registration_number')->ignore($vehicleId),
            ],
            'vehicle_type' => ['required', 'in:bus,van,car,truck'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'driver_id' => ['nullable', 'integer', $driverRule],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'assistant_name' => ['nullable', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:50'],
            'model_year' => ['nullable', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_number.required' => 'Le numero d immatriculation est obligatoire.',
            'registration_number.unique' => 'Ce numero d immatriculation existe deja.',
            'vehicle_type.required' => 'Le type de vehicule est obligatoire.',
            'capacity.required' => 'La capacite est obligatoire.',
            'capacity.min' => 'La capacite doit etre au moins 1.',
            'driver_id.exists' => 'Le conducteur selectionne est invalide pour cette ecole.',
            'model_year.integer' => 'L annee du modele doit etre un nombre.',
            'model_year.min' => 'L annee du modele ne peut pas etre avant 1990.',
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
