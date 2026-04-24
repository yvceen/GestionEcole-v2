<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('classroom_id')->index();

            $table->string('reason')->nullable(); // faiblesse en math...
            $table->text('actions')->nullable(); // plan de soutien
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_plans');
    }
};
