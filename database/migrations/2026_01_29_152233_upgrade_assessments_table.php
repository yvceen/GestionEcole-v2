<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {

            // classroom_id
            if (!Schema::hasColumn('assessments', 'classroom_id')) {
                $table->unsignedBigInteger('classroom_id')->nullable()->after('teacher_id');
                $table->index(['classroom_id']);
            }

            // subject_id
            if (!Schema::hasColumn('assessments', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('classroom_id');
                $table->index(['subject_id']);
            }

            // title
            if (!Schema::hasColumn('assessments', 'title')) {
                $table->string('title')->nullable()->after('subject_id');
            }

            // type
            if (!Schema::hasColumn('assessments', 'type')) {
                $table->string('type')->nullable()->after('title');
            }

            // date (راه عندك ولكن نخليها safe)
            if (!Schema::hasColumn('assessments', 'date')) {
                $table->date('date')->nullable()->after('type');
                $table->index(['date']);
            }

            // coefficient
            if (!Schema::hasColumn('assessments', 'coefficient')) {
                $table->decimal('coefficient', 5, 2)->nullable()->default(1)->after('date');
            }

            // school_id (احتياط)
            if (!Schema::hasColumn('assessments', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
                $table->index(['school_id']);
            }

            // teacher_id (احتياط)
            if (!Schema::hasColumn('assessments', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('school_id');
                $table->index(['teacher_id']);
            }
        });
    }

    public function down(): void
    {
        // ما غاديش نحيدو الأعمدة فـ down باش مانكسروش الداتا
    }
};
