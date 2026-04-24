<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_councils', function (Blueprint $table) {

            if (!Schema::hasColumn('class_councils', 'classroom_id')) {
                $table->foreignId('classroom_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('class_councils', 'date')) {
                $table->date('date')->nullable()->after('classroom_id');
            }

            if (!Schema::hasColumn('class_councils', 'title')) {
                $table->string('title')->nullable()->after('date');
            }

            if (!Schema::hasColumn('class_councils', 'decisions')) {
                $table->text('decisions')->nullable()->after('title');
            }

            if (!Schema::hasColumn('class_councils', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('decisions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_councils', function (Blueprint $table) {

            if (Schema::hasColumn('class_councils', 'classroom_id')) {
                $table->dropForeign(['classroom_id']);
            }

            $table->dropColumn(
                array_filter([
                    Schema::hasColumn('class_councils', 'classroom_id') ? 'classroom_id' : null,
                    Schema::hasColumn('class_councils', 'date') ? 'date' : null,
                    Schema::hasColumn('class_councils', 'title') ? 'title' : null,
                    Schema::hasColumn('class_councils', 'decisions') ? 'decisions' : null,
                    Schema::hasColumn('class_councils', 'created_by_user_id') ? 'created_by_user_id' : null,
                ])
            );
        });
    }
};