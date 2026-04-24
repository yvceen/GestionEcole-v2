<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'scope',
        'classroom_id',
        'source_type',
        'source_id',
        'title',
        'summary',
        'body',
        'cover_path',
        'is_pinned',
        'status',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'is_pinned' => 'boolean',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereDate('date', '<=', now()->toDateString());
    }

    public function scopeVisibleToClassrooms(Builder $query, int $schoolId, array $classroomIds = []): Builder
    {
        return $query
            ->where('school_id', $schoolId)
            ->where(function (Builder $scope) use ($classroomIds): void {
                $scope->where(function (Builder $schoolScope): void {
                    $schoolScope->where(function (Builder $query): void {
                        $query->whereNull('scope')
                            ->orWhere('scope', 'school');
                    })->whereNull('classroom_id');
                });

                if ($classroomIds !== []) {
                    $scope->orWhere(function (Builder $classroomScope) use ($classroomIds): void {
                        $classroomScope
                            ->where('scope', 'classroom')
                            ->whereIn('classroom_id', $classroomIds);
                    });
                }
            });
    }

    public function getExcerptAttribute(): string
    {
        $summary = trim((string) ($this->summary ?? ''));
        if ($summary !== '') {
            return $summary;
        }

        return Str::limit(trim((string) ($this->body ?? '')), 180, '...');
    }

    public function getCoverUrlAttribute(): ?string
    {
        $path = trim((string) ($this->cover_path ?? ''));
        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
