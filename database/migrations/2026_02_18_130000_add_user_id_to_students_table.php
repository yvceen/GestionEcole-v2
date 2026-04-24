<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('parent_user_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->unique('user_id', 'students_user_id_unique');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'user_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            try {
                $table->dropUnique('students_user_id_unique');
            } catch (\Throwable $e) {
                // ignore
            }

            $table->dropConstrainedForeignId('user_id');
        });
    }
};
