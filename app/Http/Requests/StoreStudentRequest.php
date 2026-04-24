<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->currentSchoolId();
        $parentRule = Rule::exists('users', 'id');
        $classroomRule = Rule::exists('classrooms', 'id');
        $routeRule = Rule::exists('routes', 'id');
        $vehicleRule = Rule::exists('vehicles', 'id');

        if ($schoolId !== null) {
            $parentRule = $parentRule->where(fn ($query) => $query->where('school_id', $schoolId)->where('role', 'parent'));
            $classroomRule = $classroomRule->where(fn ($query) => $query->where('school_id', $schoolId));
            $routeRule = $routeRule->where(fn ($query) => $query->where('school_id', $schoolId));
            $vehicleRule = $vehicleRule->where(fn ($query) => $query->where('school_id', $schoolId));
        }

        return [
            'full_name'         => ['required', 'string', 'max:255'],
            'birth_date'        => ['nullable', 'date', 'before:today'],
            'gender'            => ['nullable', 'in:male,female'],
            'parent_user_id'    => ['nullable', 'integer', $parentRule],
            'existing_parent_user_id' => ['nullable', 'integer', $parentRule],
            'create_parent_account' => ['nullable', 'boolean'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'parent_password' => ['nullable', 'string', 'min:8'],
            'create_student_account' => ['nullable', 'boolean'],
            'student_account_email' => ['nullable', 'email', 'max:255'],
            'student_account_password' => ['nullable', 'string', 'min:8'],
            'classroom_id'      => ['required', 'integer', $classroomRule],
            'tuition_monthly'   => ['required', 'numeric', 'min:0'],
            'canteen_monthly'   => ['nullable', 'numeric', 'min:0'],
            'transport_monthly' => ['nullable', 'numeric', 'min:0'],
            'insurance_yearly'  => ['nullable', 'numeric', 'min:0'],
            'insurance_paid'    => ['nullable', 'boolean'],
            'transport_enabled' => ['nullable', 'boolean'],
            'transport_route_id' => ['nullable', 'integer', $routeRule],
            'transport_vehicle_id' => ['nullable', 'integer', $vehicleRule],
            'transport_period' => ['nullable', 'in:morning,evening,both'],
            'transport_pickup_point' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Le nom complet est obligatoire.',
            'classroom_id.required' => 'La classe est obligatoire.',
            'classroom_id.exists' => 'La classe sélectionnée est invalide.',
            'parent_user_id.exists' => 'Le parent sélectionné est invalide.',
            'birth_date.date' => 'La date de naissance doit être une date valide.',
            'birth_date.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'tuition_monthly.required' => 'Les frais de scolarité sont obligatoires.',
            'tuition_monthly.numeric' => 'Les frais de scolarité doivent être un nombre.',
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
