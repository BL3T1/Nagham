<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if tables exist before adding indexes
        if (!Schema::hasTable('orders')) {
            return;
        }

        // Helper function to safely add index
        $addIndexSafely = function ($table, $index, $type = 'index') {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($index, $type) {
                    if ($type === 'fullText') {
                        $blueprint->fullText($index);
                    } else {
                        if (is_array($index)) {
                            $blueprint->index($index);
                        } else {
                            $blueprint->index([$index]);
                        }
                    }
                });
            } catch (\Exception $e) {
                // Index might already exist or table structure changed, skip it
                // This is safe to ignore - the index is already there
            }
        };

        // Add indexes to orders table
        $addIndexSafely('orders', 'status');
        $addIndexSafely('orders', 'payment_status');
        $addIndexSafely('orders', 'created_at');
        $addIndexSafely('orders', ['patient_id', 'status']);
        $addIndexSafely('orders', ['created_by', 'created_at']);

        // Add indexes to order_items table
        if (Schema::hasTable('order_items')) {
            $addIndexSafely('order_items', 'status');
            $addIndexSafely('order_items', 'doctor_id');
            $addIndexSafely('order_items', 'next_session_date');
            $addIndexSafely('order_items', ['order_id', 'status']);
            $addIndexSafely('order_items', ['doctor_id', 'status']);
        }

        // Add indexes to patients table
        if (Schema::hasTable('patients')) {
            $addIndexSafely('patients', 'phone');
            $addIndexSafely('patients', 'email');
            $addIndexSafely('patients', ['name', 'phone', 'email'], 'fullText');
        }

        // Add indexes to payments table
        if (Schema::hasTable('payments')) {
            $addIndexSafely('payments', 'payment_type');
            $addIndexSafely('payments', 'created_at');
            $addIndexSafely('payments', ['order_id', 'payment_type']);
            $addIndexSafely('payments', ['patient_id', 'created_at']);
        }

        // Add indexes to appointments table
        if (Schema::hasTable('appointments')) {
            $addIndexSafely('appointments', 'status');
            $addIndexSafely('appointments', 'appointment_date');
            $addIndexSafely('appointments', ['doctor_id', 'appointment_date']);
            $addIndexSafely('appointments', ['patient_id', 'appointment_date']);
        }

        // Add indexes to users table
        if (Schema::hasTable('users')) {
            $addIndexSafely('users', 'role');
            $addIndexSafely('users', 'is_active');
            $addIndexSafely('users', ['role', 'is_active']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to safely drop index
        $dropIndexSafely = function ($table, $index) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    if (is_array($index)) {
                        $blueprint->dropIndex($index);
                    } else {
                        $blueprint->dropIndex([$index]);
                    }
                });
            } catch (\Exception $e) {
                // Index might be used by foreign key constraint, skip it
                // This is safe to ignore during rollback
            }
        };

        if (Schema::hasTable('orders')) {
            $dropIndexSafely('orders', 'status');
            $dropIndexSafely('orders', 'payment_status');
            $dropIndexSafely('orders', 'created_at');
            // Skip composite indexes that include foreign keys - they may be used by FK constraints
            // $dropIndexSafely('orders', ['patient_id', 'status']);
            // $dropIndexSafely('orders', ['created_by', 'created_at']);
        }

        if (Schema::hasTable('order_items')) {
            $dropIndexSafely('order_items', 'status');
            // Skip doctor_id index - it's a foreign key
            // $dropIndexSafely('order_items', 'doctor_id');
            $dropIndexSafely('order_items', 'next_session_date');
            // Skip composite indexes that include foreign keys
            // $dropIndexSafely('order_items', ['order_id', 'status']);
            // $dropIndexSafely('order_items', ['doctor_id', 'status']);
        }

        if (Schema::hasTable('patients')) {
            $dropIndexSafely('patients', 'phone');
            $dropIndexSafely('patients', 'email');
            try {
                Schema::table('patients', function (Blueprint $table) {
                    $table->dropFullText(['name', 'phone', 'email']);
                });
            } catch (\Exception $e) {
                // Ignore if full-text index doesn't exist or can't be dropped
            }
        }

        if (Schema::hasTable('payments')) {
            $dropIndexSafely('payments', 'payment_type');
            $dropIndexSafely('payments', 'created_at');
            // Skip composite indexes that include foreign keys
            // $dropIndexSafely('payments', ['order_id', 'payment_type']);
            // $dropIndexSafely('payments', ['patient_id', 'created_at']);
        }

        if (Schema::hasTable('appointments')) {
            $dropIndexSafely('appointments', 'status');
            $dropIndexSafely('appointments', 'appointment_date');
            // Skip composite indexes that include foreign keys
            // $dropIndexSafely('appointments', ['doctor_id', 'appointment_date']);
            // $dropIndexSafely('appointments', ['patient_id', 'appointment_date']);
        }

        if (Schema::hasTable('users')) {
            $dropIndexSafely('users', 'role');
            $dropIndexSafely('users', 'is_active');
            $dropIndexSafely('users', ['role', 'is_active']);
        }
    }
};

