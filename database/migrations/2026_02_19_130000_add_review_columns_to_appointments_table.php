<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('appointments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('appointments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('appointments', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $drops = [];
            foreach (['approved_at', 'approved_by', 'rejected_at', 'rejected_by'] as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};

