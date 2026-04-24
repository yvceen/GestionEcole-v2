<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'scope')) {
                $table->string('scope', 20)->default('classroom')->after('status');
            }

            if (!Schema::hasColumn('news', 'classroom_id')) {
                $table->unsignedBigInteger('classroom_id')->nullable()->after('scope')->index();
            }

            if (!Schema::hasColumn('news', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('classroom_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        Schema::table('news', function (Blueprint $table) {
            $drops = [];
            foreach (['scope', 'classroom_id', 'school_id'] as $column) {
                if (Schema::hasColumn('news', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};

