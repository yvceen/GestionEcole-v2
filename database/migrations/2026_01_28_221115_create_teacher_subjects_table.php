<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Canonical pivot table is teacher_subjects.
        // If the legacy table exists, rename it instead of creating a duplicate table.
        if (!Schema::hasTable('teacher_subjects') && Schema::hasTable('teacher_subject')) {
            Schema::rename('teacher_subject', 'teacher_subjects');
        }

        if (!Schema::hasTable('teacher_subjects')) {
            Schema::create('teacher_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('teacher_id')->index();
                $table->unsignedBigInteger('subject_id')->index();
                $table->unsignedBigInteger('assigned_by_user_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // Ensure canonical unique key exists even if table came from a rename.
        try {
            Schema::table('teacher_subjects', function (Blueprint $table) {
                $table->unique(['school_id', 'teacher_id', 'subject_id'], 'teacher_subjects_unique');
            });
        } catch (\Throwable $e) {
            // Unique already exists or cannot be created due to legacy duplicates.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
    }
};
