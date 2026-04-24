<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_councils', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('classroom_id')->index();

            $table->date('date');
            $table->string('title')->default('Conseil de classe');
            $table->text('decisions')->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_councils');
    }
};
