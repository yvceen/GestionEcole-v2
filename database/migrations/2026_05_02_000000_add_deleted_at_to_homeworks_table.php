<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homeworks')) {
            return;
        }

        Schema::table('homeworks', function (Blueprint $table) {
            if (!Schema::hasColumn('homeworks', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('homeworks') || !Schema::hasColumn('homeworks', 'deleted_at')) {
            return;
        }

        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
