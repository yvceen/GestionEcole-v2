<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'school_id')) {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'school_id')) {
                try { $table->dropForeign(['school_id']); } catch (\Throwable $e) {}
                $table->dropColumn('school_id');
            }
        });
    }
};
