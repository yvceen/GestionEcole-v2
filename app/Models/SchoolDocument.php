<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;

class SchoolDocument extends Model
{
    use BelongsToSchool;

    public const CATEGORY_ADMINISTRATIVE = 'administratif';
    public const CATEGORY_PEDAGOGICAL = 'pedagogique';
    public const CATEGORY_COMMUNICATION = 'communication';

    public const AUDIENCE_SCHOOL = 'school';
    public const AUDIENCE_CLASSROOM = 'classroom';
    public const AUDIENCE_ROLE = 'role';

    protected $fillable = [
        'school_id',
        'title',
        'summary',
        'category',
        'audience_scope',
        'role',
        'classroom_id',
        'file_path',
        'mime_type',
        'size_bytes',
        'published_at',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_active' => 'boolean',
        'size_bytes' => 'integer',
    ];

    public static function categories(): array
    {
        return [
            self::CATEGORY_ADMINISTRATIVE,
            self::CATEGORY_PEDAGOGICAL,
            self::CATEGORY_COMMUNICATION,
        ];
    }

    public static function audienceScopes(): array
    {
        return [
            self::AUDIENCE_SCHOOL,
            self::AUDIENCE_CLASSROOM,
            self::AUDIENCE_ROLE,
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleToAudience(
        Builder $query,
        int $schoolId,
        ?string $role = null,
        array $classroomIds = [],
    ): Builder {
        return $query
            ->where('school_id', $schoolId)
            ->active()
            ->where(function (Builder $scope) use ($role, $classroomIds): void {
                $scope->where('audience_scope', self::AUDIENCE_SCHOOL);

                if ($classroomIds !== []) {
                    $scope->orWhere(function (Builder $classroomScope) use ($classroomIds): void {
                        $classroomScope
                            ->where('audience_scope', self::AUDIENCE_CLASSROOM)
                            ->whereIn('classroom_id', $classroomIds);
                    });
                }

                if ($role !== null && $role !== '') {
                    $scope->orWhere(function (Builder $roleScope) use ($role): void {
                        $roleScope
                            ->where('audience_scope', self::AUDIENCE_ROLE)
                            ->where('role', $role);
                    });
                }
            });
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->exists) {
            return null;
        }

        if (Route::has('documents.download')) {
            return route('documents.download', ['document' => $this->getKey()]);
        }

        return null;
    }
}
