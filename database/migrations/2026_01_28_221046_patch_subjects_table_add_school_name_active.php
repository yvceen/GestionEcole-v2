<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('subjects', 'name')) {
                $table->string('name')->nullable()->after('school_id');
            }
            if (!Schema::hasColumn('subjects', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name');
            }
            if (!Schema::hasColumn('subjects', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // نخليها فارغة باش ما نحيدوش أعمدة عندك ف production
        });
    }
};
