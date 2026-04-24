<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subjects')) return;

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'school_id')) {
                $table->foreignId('school_id')
                    ->nullable()
                    ->constrained('schools')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('subjects', 'name')) {
                $table->string('name')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subjects')) return;

        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'name')) {
                $table->dropColumn('name');
            }

            if (Schema::hasColumn('subjects', 'school_id')) {
                try { $table->dropForeign(['school_id']); } catch (\Throwable $e) {}
                $table->dropColumn('school_id');
            }
        });
    }
};
