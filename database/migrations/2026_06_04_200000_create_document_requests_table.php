<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('custom_type')->nullable();
            $table->text('purpose')->nullable();
            $table->unsignedTinyInteger('copies')->default(1);
            $table->string('language', 10)->default('fr');
            $table->string('delivery_method')->default('pickup');
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['parent_user_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
