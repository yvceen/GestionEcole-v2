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
    Schema::table('student_fee_plans', function (Blueprint $table) {
        if (!Schema::hasColumn('student_fee_plans', 'tuition_monthly')) {
            $table->decimal('tuition_monthly', 10, 2)->default(0);
        }
        if (!Schema::hasColumn('student_fee_plans', 'transport_monthly')) {
            $table->decimal('transport_monthly', 10, 2)->default(0);
        }
        if (!Schema::hasColumn('student_fee_plans', 'canteen_monthly')) {
            $table->decimal('canteen_monthly', 10, 2)->default(0);
        }
        if (!Schema::hasColumn('student_fee_plans', 'insurance_yearly')) {
            $table->decimal('insurance_yearly', 10, 2)->default(0);
        }
        if (!Schema::hasColumn('student_fee_plans', 'starts_month')) {
            $table->unsignedTinyInteger('starts_month')->default(9);
        }
        if (!Schema::hasColumn('student_fee_plans', 'notes')) {
            $table->text('notes')->nullable();
        }
    });
}

public function down(): void
{
    Schema::table('student_fee_plans', function (Blueprint $table) {
        // اختيارياً: يمكن تحيدهم
        // $table->dropColumn([...]);
    });
}

};
