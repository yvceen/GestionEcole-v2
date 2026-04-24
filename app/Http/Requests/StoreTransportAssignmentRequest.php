<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreTransportAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    public function rules(): array
    {
        $schoolId = $this->currentSchoolId();
        $studentRule = Rule::exists('students', 'id');
        $routeRule = Rule::exists('routes', 'id');
        $vehicleRule = Rule::exists('vehicles', 'id');

        if ($schoolId !== null) {
            $studentRule = $studentRule->where(fn ($query) => $query->where('school_id', $schoolId));
            $routeRule = $routeRule->where(fn ($query) => $query->where('school_id', $schoolId));
            $vehicleRule = $vehicleRule->where(fn ($query) => $query->where('school_id', $schoolId));
        }

        return [
            'student_id' => ['required', 'integer', $studentRule],
            'vehicle_id' => ['nullable', 'integer', $vehicleRule],
            'route_id' => ['required', 'integer', $routeRule],
            'period' => ['nullable', 'in:morning,evening,both'],
            'pickup_point' => ['nullable', 'string', 'max:255'],
            'assigned_date' => ['required', 'date'],
            'ended_date' => ['nullable', 'date', 'after_or_equal:assigned_date'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.exists' => 'L eleve selectionne est invalide pour cette ecole.',
            'vehicle_id.exists' => 'Le vehicule selectionne est invalide pour cette ecole.',
            'route_id.exists' => 'La route selectionnee est invalide pour cette ecole.',
            'period.in' => 'La periode de transport est invalide.',
            'ended_date.after_or_equal' => 'La date de fin doit etre posterieure ou egale a la date d affectation.',
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
