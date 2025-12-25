<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Temporarily change column to string to allow modification
            $table->string('status_temp')->nullable();
        });

        // Copy and transform data
        DB::table('appointments')->get()->each(function ($appointment) {
            $newStatus = match ($appointment->status) {
                'scheduled', 'completed', 'cancelled' => $appointment->status,
                'confirmed', 'no_show' => 'scheduled', // Map old statuses to 'scheduled'
                default => 'scheduled', // Fallback for any unexpected values
            };
            DB::table('appointments')->where('id', $appointment->id)->update(['status_temp' => $newStatus]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled')->after('appointment_date');
        });

        // Copy data back from temp column
        DB::table('appointments')->get()->each(function ($appointment) {
            DB::table('appointments')->where('id', $appointment->id)->update(['status' => $appointment->status_temp]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status_temp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Temporarily change column to string to allow modification
            $table->string('status_temp')->nullable();
        });

        // Copy and transform data back
        DB::table('appointments')->get()->each(function ($appointment) {
            $newStatus = match ($appointment->status) {
                'scheduled' => 'scheduled',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => 'scheduled',
            };
            DB::table('appointments')->where('id', $appointment->id)->update(['status_temp' => $newStatus]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('scheduled')->after('appointment_date');
        });

        // Copy data back from temp column
        DB::table('appointments')->get()->each(function ($appointment) {
            DB::table('appointments')->where('id', $appointment->id)->update(['status' => $appointment->status_temp]);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('status_temp');
        });
    }
};
