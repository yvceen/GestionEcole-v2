<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_pedagogical_resources')) {
            return;
        }

        Schema::create('teacher_pedagogical_resources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_pedagogical_resources');
    }
};
