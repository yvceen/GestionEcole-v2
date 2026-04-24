<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_reminder_settings')) {
            return;
        }

        Schema::create('payment_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedTinyInteger('reminder_day')->nullable();
            $table->text('message_template')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reminder_settings');
    }
};
