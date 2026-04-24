<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasIndex(string $table, string $index): bool
    {
        $row = DB::selectOne(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1",
            [$table, $index]
        );
        return (bool) $row;
    }

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Intentionally empty; indexes added below after checks.
        });

        if (Schema::hasTable('users')) {
            if (!$this->hasIndex('users', 'idx_users_name')) {
                DB::statement('CREATE INDEX idx_users_name ON users (name)');
            }
            if (Schema::hasColumn('users', 'phone') && !$this->hasIndex('users', 'idx_users_phone')) {
                DB::statement('CREATE INDEX idx_users_phone ON users (phone)');
            }
            if (Schema::hasColumn('users', 'school_id') && Schema::hasColumn('users', 'role')
                && !$this->hasIndex('users', 'idx_users_school_role')) {
                DB::statement('CREATE INDEX idx_users_school_role ON users (school_id, role)');
            }
        }

        if (Schema::hasTable('students')) {
            if (!$this->hasIndex('students', 'idx_students_full_name')) {
                DB::statement('CREATE INDEX idx_students_full_name ON students (full_name)');
            }
            if (Schema::hasColumn('students', 'parent_user_id') && !$this->hasIndex('students', 'idx_students_parent')) {
                DB::statement('CREATE INDEX idx_students_parent ON students (parent_user_id)');
            }
            if (Schema::hasColumn('students', 'school_id') && !$this->hasIndex('students', 'idx_students_school')) {
                DB::statement('CREATE INDEX idx_students_school ON students (school_id)');
            }
        }

        if (Schema::hasTable('subjects')) {
            if (!$this->hasIndex('subjects', 'idx_subjects_name')) {
                DB::statement('CREATE INDEX idx_subjects_name ON subjects (name)');
            }
            if (Schema::hasColumn('subjects', 'code') && !$this->hasIndex('subjects', 'idx_subjects_code')) {
                DB::statement('CREATE INDEX idx_subjects_code ON subjects (code)');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            if ($this->hasIndex('users', 'idx_users_name')) {
                DB::statement('DROP INDEX idx_users_name ON users');
            }
            if ($this->hasIndex('users', 'idx_users_phone')) {
                DB::statement('DROP INDEX idx_users_phone ON users');
            }
            if ($this->hasIndex('users', 'idx_users_school_role')) {
                DB::statement('DROP INDEX idx_users_school_role ON users');
            }
        }
        if (Schema::hasTable('students')) {
            if ($this->hasIndex('students', 'idx_students_full_name')) {
                DB::statement('DROP INDEX idx_students_full_name ON students');
            }
            if ($this->hasIndex('students', 'idx_students_parent')) {
                DB::statement('DROP INDEX idx_students_parent ON students');
            }
            if ($this->hasIndex('students', 'idx_students_school')) {
                DB::statement('DROP INDEX idx_students_school ON students');
            }
        }
        if (Schema::hasTable('subjects')) {
            DB::statement('DROP INDEX idx_subjects_name ON subjects');
            if ($this->hasIndex('subjects', 'idx_subjects_code')) {
                DB::statement('DROP INDEX idx_subjects_code ON subjects');
            }
        }
    }
};
