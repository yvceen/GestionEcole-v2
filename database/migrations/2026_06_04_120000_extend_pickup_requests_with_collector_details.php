<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table): void {
            $table->string('pickup_person_name')->nullable()->after('reason');
            $table->string('pickup_person_relationship', 120)->nullable()->after('pickup_person_name');
            $table->string('pickup_person_phone', 40)->nullable()->after('pickup_person_relationship');
            $table->string('verification_code', 12)->nullable()->after('pickup_person_phone');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'pickup_person_name',
                'pickup_person_relationship',
                'pickup_person_phone',
                'verification_code',
            ]);
        });
    }
};
