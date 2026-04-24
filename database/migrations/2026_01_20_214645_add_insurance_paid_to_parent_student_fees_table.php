<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('parent_student_fees', function (Blueprint $table) {
        $table->boolean('insurance_paid')->default(false)->after('insurance_yearly');
        $table->dateTime('insurance_paid_at')->nullable()->after('insurance_paid');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_student_fees', function (Blueprint $table) {
            //
        });
    }
};
