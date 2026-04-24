<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // drop global unique on receipt_number
            $table->dropUnique('receipts_receipt_number_unique');

            // unique per school
            $table->unique(['school_id', 'receipt_number'], 'receipts_school_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropUnique('receipts_school_number_unique');
            $table->unique('receipt_number', 'receipts_receipt_number_unique');
        });
    }
};
