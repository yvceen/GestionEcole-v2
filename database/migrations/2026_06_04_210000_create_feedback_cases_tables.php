<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 30)->unique();
            $table->string('kind');
            $table->string('category');
            $table->string('subject');
            $table->text('description');
            $table->string('priority')->default('normal');
            $table->string('status')->default('new');
            $table->boolean('is_confidential')->default(false);
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'priority']);
            $table->index(['submitted_by_user_id', 'created_at']);
        });

        Schema::create('feedback_case_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feedback_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['feedback_case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_case_messages');
        Schema::dropIfExists('feedback_cases');
    }
};
