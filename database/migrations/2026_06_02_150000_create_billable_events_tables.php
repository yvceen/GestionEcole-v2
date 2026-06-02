<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billable_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount_per_student', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'event_date']);
        });

        Schema::create('billable_event_students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billable_event_id')->constrained('billable_events')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->boolean('is_exempt')->default(false);
            $table->string('exemption_reason')->nullable();
            $table->timestamps();

            $table->unique(['billable_event_id', 'student_id'], 'billable_event_student_unique');
            $table->index(['school_id', 'student_id']);
        });

        Schema::create('billable_event_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billable_event_id')->constrained('billable_events')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_number');
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('cash');
            $table->dateTime('paid_at');
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'receipt_number'], 'billable_event_receipt_unique');
            $table->index(['school_id', 'billable_event_id']);
            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'parent_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billable_event_payments');
        Schema::dropIfExists('billable_event_students');
        Schema::dropIfExists('billable_events');
    }
};
