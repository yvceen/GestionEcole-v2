<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use BelongsToSchool;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_DIRECTOR    = 'director';
    public const ROLE_TEACHER     = 'teacher';
    public const ROLE_PARENT      = 'parent';
    public const ROLE_STUDENT     = 'student';
    public const ROLE_CHAUFFEUR   = 'chauffeur';
    public const ROLE_SCHOOL_LIFE = 'school_life';

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'card_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(\App\Models\ParentProfile::class, 'user_id');
    }

    public function children()
    {
        return $this->hasMany(\App\Models\Student::class, 'parent_user_id');
    }

    public function studentProfile()
    {
        return $this->hasOne(\App\Models\Student::class, 'user_id');
    }

    // ✅ assign teacher ↔ classrooms (موجود عندك و خدام)
    public function teacherClassrooms(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Classroom::class, 'classroom_teacher', 'teacher_id', 'classroom_id')
            ->withPivot(['school_id', 'assigned_by_user_id'])
            ->withTimestamps();
    }

    // ✅ teacher ↔ subjects (matières)
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Subject::class, 'teacher_subjects', 'teacher_id', 'subject_id')
            ->withPivot(['school_id', 'assigned_by_user_id'])
            ->withTimestamps();
    }

    // ✅ Notes données par l’enseignant
    public function gradesGiven(): HasMany
    {
        return $this->hasMany(\App\Models\Grade::class, 'teacher_id');
    }

    // ✅ StudentNote (إلا كانت عندك)
    public function studentNotes(): HasMany
    {
        return $this->hasMany(\App\Models\StudentNote::class, 'created_by_user_id');
    }

    public function timetableSlots(): HasMany
    {
        return $this->hasMany(\App\Models\Timetable::class, 'teacher_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(\App\Models\AppNotification::class, 'recipient_user_id');
    }

    public function activityReports(): HasMany
    {
        return $this->hasMany(\App\Models\ActivityReport::class, 'created_by_user_id');
    }

    public function studentBehaviorsCreated(): HasMany
    {
        return $this->hasMany(\App\Models\StudentBehavior::class, 'created_by_user_id');
    }

    public function transportLogsRecorded(): HasMany
    {
        return $this->hasMany(\App\Models\TransportLog::class, 'recorded_by_user_id');
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(\App\Models\DeviceToken::class);
    }

    public function pedagogicalResources(): HasMany
    {
        return $this->hasMany(\App\Models\TeacherPedagogicalResource::class, 'teacher_id');
    }

    public function tokens(): MorphMany
    {
        return $this->morphMany(\App\Models\PersonalAccessToken::class, 'tokenable');
    }

    public function parentNotifications(): HasMany
    {
        return $this->notifications();
    }

    public static function roleLabels(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_DIRECTOR => 'Directeur',
            self::ROLE_TEACHER => 'Enseignant',
            self::ROLE_PARENT => 'Parent',
            self::ROLE_STUDENT => 'Eleve',
            self::ROLE_CHAUFFEUR => 'Chauffeur',
            self::ROLE_SCHOOL_LIFE => 'Responsable scolaire',
        ];
    }

    public static function labelForRole(?string $role): string
    {
        $role = (string) $role;

        return static::roleLabels()[$role] ?? Str::headline(str_replace('_', ' ', $role));
    }

    public static function badgeVariantForRole(?string $role): string
    {
        return match ((string) $role) {
            self::ROLE_SUPER_ADMIN => 'warning',
            self::ROLE_ADMIN => 'info',
            self::ROLE_DIRECTOR => 'info',
            self::ROLE_TEACHER => 'success',
            self::ROLE_PARENT => 'info',
            self::ROLE_STUDENT => 'success',
            self::ROLE_CHAUFFEUR => 'warning',
            self::ROLE_SCHOOL_LIFE => 'warning',
            default => 'info',
        };
    }
}
