<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Ajouter school_id si ماكاش
        if (!Schema::hasColumn('assessments', 'school_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                // نخليها nullable باش ما يطيحش migrate على data القديمة
                $table->unsignedBigInteger('school_id')->nullable()->after('id')->index();
            });
        }

        // ✅ Backfill سريع (اختياري)
        // إلا كانت assessments مرتبطة بـ classroom_id و classrooms فيها school_id
        if (Schema::hasColumn('assessments', 'classroom_id')) {
            DB::statement("
                UPDATE assessments a
                JOIN classrooms c ON c.id = a.classroom_id
                SET a.school_id = c.school_id
                WHERE a.school_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'school_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};