<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Vehicles table
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('registration_number', 50)->unique();
            $table->enum('vehicle_type', ['bus', 'van', 'car', 'truck'])->default('bus');
            $table->integer('capacity')->default(50);
            $table->unsignedBigInteger('driver_id')->nullable()->index();
            $table->string('plate_number', 20)->nullable();
            $table->string('color', 50)->nullable();
            $table->year('model_year')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
        });

        // Routes table
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('route_name', 255);
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            $table->string('start_point', 255);
            $table->string('end_point', 255);
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->integer('estimated_minutes')->nullable();
            $table->text('stops')->nullable(); // JSON array of stops
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
        });

        // Transport Assignments table
        Schema::create('transport_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('route_id')->index();
            $table->string('pickup_point', 255)->nullable();
            $table->date('assigned_date')->index();
            $table->date('ended_date')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_assignments');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('vehicles');
    }
};
