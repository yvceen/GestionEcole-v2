<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('course_attachments', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_attachments', function (Blueprint $table) {
            if (Schema::hasColumn('course_attachments', 'school_id')) {
                $table->dropColumn('school_id');
            }
        });
    }
};