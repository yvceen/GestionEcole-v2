<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_name');
            $table->string('phone', 40)->nullable();
            $table->string('identity_type', 60)->nullable();
            $table->string('identity_number', 120)->nullable();
            $table->string('organization')->nullable();
            $table->string('vehicle_plate', 60)->nullable();
            $table->string('purpose');
            $table->text('purpose_details')->nullable();
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('expected_at')->nullable();
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->string('status')->default('expected');
            $table->string('badge_code', 40)->nullable();
            $table->text('entry_note')->nullable();
            $table->text('exit_note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_in_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status', 'expected_at']);
            $table->index(['school_id', 'checked_in_at', 'checked_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_visits');
    }
};
