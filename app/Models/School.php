<?php

namespace App\Models;

use App\Services\SchoolDomainService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    protected static function booted()
    {
        static::creating(function (School $school): void {
            if (!Schema::hasColumn('schools', 'subdomain')) {
                return;
            }

            $source = (string) ($school->subdomain ?: $school->name ?: $school->slug ?: 'school');
            $school->subdomain = app(SchoolDomainService::class)->generateUniqueSubdomain($source);
        });

        static::updating(function (School $school): void {
            if (!Schema::hasColumn('schools', 'subdomain') || !$school->isDirty('subdomain')) {
                return;
            }

            $source = (string) ($school->subdomain ?: $school->getOriginal('subdomain') ?: $school->name ?: $school->slug ?: 'school');
            $school->subdomain = app(SchoolDomainService::class)->generateUniqueSubdomain($source, $school->id);
        });

        static::deleting(function (School $school): void {
            DB::transaction(function () use ($school): void {
                $schoolId = $school->id;

                if (Schema::hasTable('route_stops') && Schema::hasTable('routes')) {
                    $routeIds = DB::table('routes')->where('school_id', $schoolId)->pluck('id');
                    if ($routeIds->isNotEmpty()) {
                        DB::table('route_stops')->whereIn('route_id', $routeIds)->delete();
                    }
                }

                $tablesInOrder = [
                    'student_academic_years',
                    'academic_years',
                    'activity_reports',
                    'activity_participants',
                    'activities',
                    'student_behaviors',
                    'transport_logs',
                    'course_attachments',
                    'homework_attachments',
                    'transport_assignments',
                    'routes',
                    'vehicles',
                    'messages',
                    'news',
                    'appointments',
                    'school_lives',
                    'school_calendar_events',
                    'events',
                    'grades',
                    'assessments',
                    'teacher_subjects',
                    'courses',
                    'homeworks',
                    'payment_items',
                    'payments',
                    'receipts',
                    'parent_student_fees',
                    'student_fee_plans',
                    'classroom_fees',
                    'fee_items',
                    'device_tokens',
                    'parent_profiles',
                    'student_notes',
                    'subjects',
                    'students',
                    'classrooms',
                    'levels',
                    'users',
                ];

                foreach ($tablesInOrder as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'school_id')) {
                        DB::table($table)->where('school_id', $schoolId)->delete();
                    }
                }

                try {
                    Storage::disk('public')->deleteDirectory("schools/{$schoolId}");
                } catch (\Throwable $e) {
                    // Ignore storage cleanup failures.
                }
            });
        });
    }

    public function appUrl(): string
    {
        return app(SchoolDomainService::class)->schoolUrl((string) $this->subdomain);
    }
}
