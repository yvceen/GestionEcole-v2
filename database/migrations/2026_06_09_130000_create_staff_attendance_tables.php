<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 80);
            $table->string('employee_name')->nullable();
            $table->string('department_code', 80)->nullable();
            $table->string('department_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'employee_code'], 'staff_map_school_code_unique');
            $table->index(['school_id', 'user_id']);
            $table->index(['school_id', 'is_active']);
        });

        Schema::create('staff_attendance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 80);
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('department_code', 80)->nullable();
            $table->string('department_name')->nullable();
            $table->dateTime('punched_at');
            $table->date('punch_date');
            $table->time('punch_time')->nullable();
            $table->string('verify_type', 80)->nullable();
            $table->string('punch_state', 80)->nullable();
            $table->string('work_code', 80)->nullable();
            $table->string('card_number', 120)->nullable();
            $table->string('area_name')->nullable();
            $table->string('terminal_alias')->nullable();
            $table->string('terminal_sn', 120)->nullable();
            $table->string('source_file')->nullable();
            $table->text('raw_line')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'employee_code', 'punched_at', 'terminal_sn'], 'staff_attendance_unique_punch');
            $table->index(['school_id', 'punch_date']);
            $table->index(['school_id', 'user_id', 'punch_date']);
            $table->index(['school_id', 'department_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_logs');
        Schema::dropIfExists('staff_attendance_mappings');
    }
};
