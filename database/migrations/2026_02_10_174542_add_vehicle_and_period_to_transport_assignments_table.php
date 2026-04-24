<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transport_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('transport_assignments', 'vehicle_id')) {
                $table->foreignId('vehicle_id')
                    ->nullable()
                    ->after('route_id')
                    ->constrained('vehicles')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('transport_assignments', 'period')) {
                $table->enum('period', ['morning', 'evening', 'both'])
                    ->default('both')
                    ->after('vehicle_id');
            }

            // indexes (اختياري ولكن مزيان للأداء)
            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'vehicle_id']);
            $table->index(['school_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transport_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('transport_assignments', 'vehicle_id')) {
                $table->dropConstrainedForeignId('vehicle_id');
            }
            if (Schema::hasColumn('transport_assignments', 'period')) {
                $table->dropColumn('period');
            }
        });
    }
};
