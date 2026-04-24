<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('requested_pickup_at');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'requested_pickup_at']);
            $table->index(['parent_user_id', 'requested_pickup_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
