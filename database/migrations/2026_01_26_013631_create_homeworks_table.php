<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('classroom_id')->index();
            $table->unsignedBigInteger('teacher_id')->index();

            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable(); // deadline

            $table->timestamps();

            // FK (cascade hard delete)
            $table->foreign('school_id')
                ->references('id')->on('schools')
                ->onDelete('cascade');

            $table->foreign('classroom_id')
                ->references('id')->on('classrooms')
                ->onDelete('cascade');

            $table->foreign('teacher_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->index(['school_id', 'classroom_id']);
            $table->index(['school_id', 'teacher_id']);
        });

        Schema::create('homework_attachments', function (Blueprint $table) {
            $table->id();

            // ✅ multi-school (باش نقدرونفلطرو و مانطيحوش ف school_id error)
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('homework_id')->index();

            $table->string('original_name');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();

            $table->foreign('homework_id')
                ->references('id')->on('homeworks')
                ->onDelete('cascade');

            $table->index(['school_id', 'homework_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_attachments');
        Schema::dropIfExists('homeworks');
    }
};