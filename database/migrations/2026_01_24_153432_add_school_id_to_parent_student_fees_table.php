<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_student_fees', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_student_fees', 'school_id')) {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->cascadeOnDelete();
            }

            // ✅ باش مايبقاش parent+student كيتكرر ف نفس school
            // (اختياري، ولكن مفيد)
            $table->unique(['school_id','parent_user_id','student_id'], 'psf_school_parent_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('parent_student_fees', function (Blueprint $table) {
            // drop unique if exists
            try {
                $table->dropUnique('psf_school_parent_student_unique');
            } catch (\Throwable $e) {}

            if (Schema::hasColumn('parent_student_fees', 'school_id')) {
                $table->dropConstrainedForeignId('school_id');
            }
        });
    }
};
