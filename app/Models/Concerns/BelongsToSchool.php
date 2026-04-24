<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait BelongsToSchool
{
    protected static array $schoolColumnCache = [];

    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $builder): void {
            if (!app()->bound('current_school_id')) {
                return;
            }

            $schoolId = app('current_school_id');
            if (empty($schoolId)) {
                return;
            }

            $model = $builder->getModel();
            if (!static::usesSchoolColumn($model)) {
                return;
            }

            $builder->where($model->getTable() . '.school_id', (int) $schoolId);
        });

        static::creating(function (Model $model): void {
            if (!app()->bound('current_school_id')) {
                return;
            }

            if (!static::usesSchoolColumn($model)) {
                return;
            }

            $schoolId = app('current_school_id');

            if (!empty($schoolId) && empty($model->school_id)) {
                $model->school_id = (int) $schoolId;
            }
        });
    }

    public static function currentSchoolId(): ?int
    {
        if (!app()->bound('current_school_id')) {
            return null;
        }

        $id = app('current_school_id');

        return $id ? (int) $id : null;
    }

    public function school()
    {
        return $this->belongsTo(\App\Models\School::class);
    }

    protected static function usesSchoolColumn(Model $model): bool
    {
        $table = $model->getTable();

        if (!array_key_exists($table, static::$schoolColumnCache) || static::$schoolColumnCache[$table] === false) {
            static::$schoolColumnCache[$table] = Schema::hasTable($table)
                && Schema::hasColumn($table, 'school_id');
        }

        return static::$schoolColumnCache[$table];
    }
}
