<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (!Schema::hasColumn('students', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('classroom_id')->index();
            }

            if (!Schema::hasColumn('students', 'archived_by_user_id')) {
                $table->foreignId('archived_by_user_id')
                    ->nullable()
                    ->after('archived_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('students', 'archive_reason')) {
                $table->string('archive_reason', 500)->nullable()->after('archived_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'archived_by_user_id')) {
                $table->dropConstrainedForeignId('archived_by_user_id');
            }

            if (Schema::hasColumn('students', 'archive_reason')) {
                $table->dropColumn('archive_reason');
            }

            if (Schema::hasColumn('students', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
