<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_pedagogic_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();

            $table->integer('year')->index();
            $table->integer('month')->index(); // 1..12

            $table->text('summary')->nullable();
            $table->text('recommendations')->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();

            // ✅ UNIQUE باسم قصير باش ما يطيحش MySQL
            $table->unique(
                ['school_id', 'classroom_id', 'year', 'month'],
                'mpr_school_class_year_month_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_pedagogic_reports');
    }
};