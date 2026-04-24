<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments') || Schema::hasColumn('appointments', 'student_id')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->after('parent_id')->index();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('appointments') || !Schema::hasColumn('appointments', 'student_id')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
        });
    }
};
