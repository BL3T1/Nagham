<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Change column to VARCHAR temporarily to allow any value
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` VARCHAR(20)");

        // Step 2: Update existing data: convert 'pending' and 'partial' to 'not_paid'
        DB::table('orders')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->update(['payment_status' => 'not_paid']);

        // Step 3: Change back to ENUM with new values
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` ENUM('paid', 'not_paid') DEFAULT 'not_paid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Change column to VARCHAR temporarily
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` VARCHAR(20)");

        // Step 2: Convert 'not_paid' back to 'pending'
        DB::table('orders')
            ->where('payment_status', 'not_paid')
            ->update(['payment_status' => 'pending']);

        // Step 3: Revert to original enum values
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` ENUM('pending', 'partial', 'paid') DEFAULT 'pending'");
    }
};
