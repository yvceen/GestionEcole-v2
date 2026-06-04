<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentHealthProfile extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'blood_type', 'allergies', 'chronic_conditions',
        'medications', 'dietary_restrictions', 'emergency_instructions',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
        'doctor_name', 'doctor_phone', 'allow_first_aid', 'updated_by_user_id',
    ];

    protected $casts = ['allow_first_aid' => 'boolean'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
