<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        // users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
        });

        // levels
        Schema::table('levels', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
        });

        // classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
        });

        // students
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
        });

        // payments
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
        });

        // receipts
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });
    }
};
