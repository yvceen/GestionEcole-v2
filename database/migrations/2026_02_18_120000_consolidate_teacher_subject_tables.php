<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasLegacy = Schema::hasTable('teacher_subject');
        $hasCanonical = Schema::hasTable('teacher_subjects');

        if (!$hasLegacy && !$hasCanonical) {
            return;
        }

        // If only legacy exists, rename directly to canonical table.
        if ($hasLegacy && !$hasCanonical) {
            Schema::rename('teacher_subject', 'teacher_subjects');
            return;
        }

        // If both exist, merge data from legacy into canonical (safe, no silent drops).
        if ($hasLegacy && $hasCanonical) {
            DB::table('teacher_subject as ts')
                ->leftJoin('users as u', 'u.id', '=', 'ts.teacher_id')
                ->select([
                    'ts.id',
                    DB::raw('COALESCE(ts.school_id, u.school_id) as school_id'),
                    'ts.teacher_id',
                    'ts.subject_id',
                    'ts.assigned_by_user_id',
                    'ts.created_at',
                    'ts.updated_at',
                ])
                ->whereNotNull('ts.teacher_id')
                ->whereNotNull('ts.subject_id')
                ->orderBy('ts.id')
                ->chunk(500, function ($rows): void {
                    $payload = [];

                    foreach ($rows as $row) {
                        if (empty($row->school_id)) {
                            continue;
                        }

                        $payload[] = [
                            'school_id' => (int) $row->school_id,
                            'teacher_id' => (int) $row->teacher_id,
                            'subject_id' => (int) $row->subject_id,
                            'assigned_by_user_id' => $row->assigned_by_user_id ? (int) $row->assigned_by_user_id : null,
                            'created_at' => $row->created_at ?? now(),
                            'updated_at' => $row->updated_at ?? now(),
                        ];
                    }

                    if (empty($payload)) {
                        return;
                    }

                    DB::table('teacher_subjects')->upsert(
                        $payload,
                        ['school_id', 'teacher_id', 'subject_id'],
                        ['assigned_by_user_id', 'updated_at']
                    );
                });

            // Keep legacy data without dropping: rename to explicit backup table if possible.
            if (!Schema::hasTable('teacher_subject_legacy')) {
                try {
                    Schema::rename('teacher_subject', 'teacher_subject_legacy');
                } catch (\Throwable $e) {
                    // If rename is not possible in this environment, keep legacy table unchanged.
                }
            }
        }

        // Ensure canonical unique index exists.
        try {
            Schema::table('teacher_subjects', function (Blueprint $table): void {
                $table->unique(['school_id', 'teacher_id', 'subject_id'], 'teacher_subjects_unique');
            });
        } catch (\Throwable $e) {
            // Index already exists.
        }
    }

    public function down(): void
    {
        // No destructive rollback for data-safety reasons.
    }
};
