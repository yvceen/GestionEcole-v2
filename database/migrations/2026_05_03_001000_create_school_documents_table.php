<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_documents')) {
            return;
        }

        Schema::create('school_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('category', 40)->default('administratif')->index();
            $table->string('audience_scope', 20)->default('school')->index();
            $table->string('role', 40)->nullable()->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
            $table->string('file_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_documents');
    }
};
