<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_behaviors')) {
            return;
        }

        Schema::table('student_behaviors', function (Blueprint $table): void {
            if (!Schema::hasColumn('student_behaviors', 'visible_to_parent')) {
                $table->boolean('visible_to_parent')->default(false)->after('description')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_behaviors')) {
            return;
        }

        Schema::table('student_behaviors', function (Blueprint $table): void {
            if (Schema::hasColumn('student_behaviors', 'visible_to_parent')) {
                $table->dropColumn('visible_to_parent');
            }
        });
    }
};
