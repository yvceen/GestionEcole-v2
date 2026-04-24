<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_app_configs')) {
            return;
        }

        Schema::create('mobile_app_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('platform', 20)->default('all')->index();
            $table->string('latest_version', 32);
            $table->string('minimum_supported_version', 32)->nullable();
            $table->text('update_message')->nullable();
            $table->string('update_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_configs');
    }
};
