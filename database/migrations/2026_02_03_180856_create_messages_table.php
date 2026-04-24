<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Multi-school context (same DB, different school_id)
            $table->unsignedBigInteger('school_id')->index();

            // Sender
            $table->unsignedBigInteger('sender_id')->index();
            $table->string('sender_role', 30)->nullable();

            // Recipient (user OR classroom)
            $table->enum('recipient_type', ['user', 'classroom'])->index();
            $table->unsignedBigInteger('recipient_id')->index(); // user_id OR classroom_id

            // Main content
            $table->string('subject', 255)->nullable();
            $table->longText('body');

            /**
             * Status:
             * draft: optional later
             * pending: teacher->parents requires admin approval
             * approved: approved by admin, visible to recipients
             * rejected: rejected by admin
             */
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->index();

            // Admin moderation
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};