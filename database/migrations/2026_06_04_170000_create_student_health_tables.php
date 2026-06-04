<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_health_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('blood_type', 10)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('emergency_instructions')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 40)->nullable();
            $table->string('emergency_contact_relationship', 120)->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_phone', 40)->nullable();
            $table->boolean('allow_first_aid')->default(true);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
        });

        Schema::create('student_health_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('school');
            $table->string('type')->default('illness');
            $table->string('severity')->default('medium');
            $table->string('condition_name');
            $table->text('symptoms')->nullable();
            $table->text('instructions')->nullable();
            $table->dateTime('starts_at');
            $table->date('expected_return_at')->nullable();
            $table->string('status')->default('active');
            $table->boolean('visible_to_teacher')->default(true);
            $table->boolean('visible_to_driver')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status', 'starts_at']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_health_reports');
        Schema::dropIfExists('student_health_profiles');
    }
};
