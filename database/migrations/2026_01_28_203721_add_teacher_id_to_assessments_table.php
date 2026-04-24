<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إذا table ماكايناش أصلاً: خليها كتدوز بلا crash
        if (!Schema::hasTable('assessments')) {
            return;
        }

        // ✅ teacher_id
        if (!Schema::hasColumn('assessments', 'teacher_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->foreignId('teacher_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('assessments')) return;

        if (Schema::hasColumn('assessments', 'teacher_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                // drop FK first
                try { $table->dropForeign(['teacher_id']); } catch (\Throwable $e) {}
                $table->dropColumn('teacher_id');
            });
        }
    }
};
