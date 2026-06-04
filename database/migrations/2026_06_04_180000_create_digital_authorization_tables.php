<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_authorizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category')->default('other');
            $table->text('description');
            $table->text('instructions')->nullable();
            $table->dateTime('event_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('status')->default('published');
            $table->boolean('requires_comment')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status', 'due_at']);
        });

        Schema::create('digital_authorization_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('digital_authorization_id');
            $table->foreign('digital_authorization_id', 'digital_auth_recipient_auth_fk')
                ->references('id')
                ->on('digital_authorizations')
                ->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('response_comment')->nullable();
            $table->string('signed_name')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->string('response_ip', 64)->nullable();
            $table->text('response_user_agent')->nullable();
            $table->timestamps();

            $table->unique(['digital_authorization_id', 'student_id'], 'digital_authorization_student_unique');
            $table->index(['school_id', 'status', 'parent_user_id'], 'digital_authorization_recipient_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_authorization_recipients');
        Schema::dropIfExists('digital_authorizations');
    }
};
