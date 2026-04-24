<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_plans', 'insurance_paid')) {
                $table->boolean('insurance_paid')->default(false)->after('insurance_yearly');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table) {
            if (Schema::hasColumn('student_fee_plans', 'insurance_paid')) {
                $table->dropColumn('insurance_paid');
            }
        });
    }
};
