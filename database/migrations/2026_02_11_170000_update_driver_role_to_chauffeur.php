<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'driver')
            ->update(['role' => 'chauffeur']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'chauffeur')
            ->update(['role' => 'driver']);
    }
};
