<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // school scope (إلا عندك multi-school)
            $table->unsignedBigInteger('school_id')->nullable()->index();

            // who created it
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('parent_name');          // اسم ولي الأمر
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();

            // content
            $table->string('title');
            $table->text('message')->nullable();

            // schedule
            $table->dateTime('scheduled_at')->index();

            // status workflow
            $table->string('status')->default('pending')->index(); 
            // pending | confirmed | cancelled

            // admin notes (optional)
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};