<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            // ✅ drop old unique(code)
            $table->dropUnique('levels_code_unique');

            // ✅ new unique per school
            $table->unique(['school_id', 'code'], 'levels_school_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropUnique('levels_school_code_unique');
            $table->unique('code', 'levels_code_unique');
        });
    }
};
