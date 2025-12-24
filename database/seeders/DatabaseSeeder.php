<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $doctor = User::create([
            'name' => 'Doctor One',
            'email' => 'doctor@example.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'is_active' => true,
        ]);

        $reception = User::create([
            'name' => 'Reception User',
            'email' => 'reception@example.com',
            'password' => Hash::make('password'),
            'role' => 'reception',
            'is_active' => true,
        ]);

        // Create additional users using factory
        $doctors = User::factory()
            ->count(5)
            ->doctor()
            ->active()
            ->create();

        $receptionists = User::factory()
            ->count(2)
            ->reception()
            ->active()
            ->create();

        // Create patients using factory
        $patients = Patient::factory()
            ->count(30)
            ->create();

        // Create orders with relationships
        $orders = Order::factory()
            ->count(50)
            ->create([
                'patient_id' => function () use ($patients) {
                    return $patients->random()->id;
                },
                'created_by' => function () use ($reception, $receptionists) {
                    return collect([$reception])->merge($receptionists)->random()->id;
                },
            ]);

        // Create order items for orders and collect them for appointments
        $orderItemsForAppointments = collect();
        
        foreach ($orders as $order) {
            // Each order can have 1-5 order items
            $itemCount = rand(1, 5);
            $items = OrderItem::factory()
                ->count($itemCount)
                ->create([
                    'order_id' => $order->id,
                    'doctor_id' => function () use ($doctor, $doctors) {
                        return collect([$doctor])->merge($doctors)->random()->id;
                    },
                ]);
            
            // Collect some order items for appointments (about 50% of items)
            $itemsCountToLink = min(ceil($itemCount * 0.5), $items->count());
            $orderItemsForAppointments = $orderItemsForAppointments->merge(
                $items->shuffle()->take($itemsCountToLink)
            );
            
            // Update order amounts after items are created
            $order->updateAmounts();
        }

        // Create appointments linked to order items
        foreach ($orderItemsForAppointments->take(30) as $orderItem) {
            Appointment::factory()
                ->create([
                    'patient_id' => $orderItem->order->patient_id,
                    'doctor_id' => $orderItem->doctor_id,
                    'order_item_id' => $orderItem->id,
                ]);
        }

        // Create some standalone appointments (not linked to order items)
        Appointment::factory()
            ->count(10)
            ->create([
                'patient_id' => function () use ($patients) {
                    return $patients->random()->id;
                },
                'doctor_id' => function () use ($doctor, $doctors) {
                    return collect([$doctor])->merge($doctors)->random()->id;
                },
                'order_item_id' => null,
            ]);

        // Create payments for orders
        foreach ($orders as $order) {
            $paymentCount = rand(0, 3);
            $totalPaid = 0;
            
            for ($i = 0; $i < $paymentCount; $i++) {
                $remainingAmount = $order->total_amount - $totalPaid;
                
                if ($remainingAmount <= 0) {
                    break;
                }
                
                $paymentAmount = min(
                    fake()->randomFloat(2, 10, $remainingAmount),
                    $remainingAmount
                );
                
                Payment::factory()
                    ->create([
                        'order_id' => $order->id,
                        'patient_id' => $order->patient_id,
                        'received_by' => function () use ($reception, $receptionists) {
                            return collect([$reception])->merge($receptionists)->random()->id;
                        },
                        'amount' => $paymentAmount,
                        'payment_type' => $totalPaid + $paymentAmount >= $order->total_amount 
                            ? 'full' 
                            : ($totalPaid > 0 ? 'partial' : fake()->randomElement(['full', 'partial', 'installment'])),
                    ]);
                
                $totalPaid += $paymentAmount;
            }
            
            // Update order amounts after payments are created
            $order->updateAmounts();
        }
    }
}
