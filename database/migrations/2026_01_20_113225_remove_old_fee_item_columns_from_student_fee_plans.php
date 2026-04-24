<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table) {
            // حيد columns القدام إلا كانو كاينين
            if (Schema::hasColumn('student_fee_plans', 'fee_item_id')) {
                $table->dropForeign(['fee_item_id']); // إلا كان foreign key
                $table->dropColumn('fee_item_id');
            }

            if (Schema::hasColumn('student_fee_plans', 'amount')) {
                $table->dropColumn('amount');
            }

            if (Schema::hasColumn('student_fee_plans', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('student_fee_plans', 'end_date')) {
                $table->dropColumn('end_date');
            }

            if (Schema::hasColumn('student_fee_plans', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    public function down(): void
    {
        // ما ضروريش ترجعهم دابا
    }
};
