<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_panel()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_reception_can_access_reception_panel()
    {
        $reception = User::factory()->create(['role' => 'reception', 'is_active' => true]);

        $response = $this->actingAs($reception)->get('/reception');

        $response->assertStatus(200);
    }

    public function test_doctor_can_access_doctor_panel()
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'is_active' => true]);

        $response = $this->actingAs($doctor)->get('/doctor');

        $response->assertStatus(200);
    }

    public function test_unauthorized_access_is_forbidden()
    {
        $reception = User::factory()->create(['role' => 'reception', 'is_active' => true]);

        // Reception trying to access admin panel
        $response = $this->actingAs($reception)->get('/admin');
        
        // Filament usually redirects to login or shows 403. 
        // Based on the User::canAccessPanel logic:
        // 'admin' => $this->role === 'admin'
        // If false, Filament forbids access.
        $response->assertStatus(403);
    }

    public function test_patient_creation()
    {
        $patientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'address' => '123 Main St',
        ];

        $patient = Patient::create($patientData);

        $this->assertDatabaseHas('patients', ['email' => 'john@example.com']);
        $this->assertEquals(34, $patient->age); // Assuming current year is 2024/2025, adjust if needed or mock time
    }

    public function test_order_creation_and_totals_calculation()
    {
        $reception = User::factory()->create(['role' => 'reception']);
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = Patient::factory()->create();

        // 1. Create Order
        $order = Order::create([
            'patient_id' => $patient->id,
            'created_by' => $reception->id,
            'status' => 'pending',
        ]);

        // 2. Add Order Items
        $item1 = OrderItem::create([
            'order_id' => $order->id,
            'doctor_id' => $doctor->id,
            'price' => 100.00,
            'status' => 'pending',
        ]);

        $item2 = OrderItem::create([
            'order_id' => $order->id,
            'doctor_id' => $doctor->id,
            'price' => 50.00,
            'status' => 'pending',
        ]);

        // 3. Update Amounts
        $order->updateAmounts();

        $this->assertEquals(150.00, $order->total_amount);
        $this->assertEquals(150.00, $order->remaining_amount);
        $this->assertEquals('pending', $order->payment_status);

        // 4. Make Partial Payment
        Payment::create([
            'order_id' => $order->id,
            'patient_id' => $patient->id,
            'received_by' => $reception->id,
            'amount' => 50.00,
            'payment_type' => 'partial',
            'payment_method' => 'cash',
        ]);

        $order->updateAmounts();

        $this->assertEquals(150.00, $order->total_amount);
        $this->assertEquals(50.00, $order->paid_amount);
        $this->assertEquals(100.00, $order->remaining_amount);
        $this->assertEquals('partial', $order->payment_status);

        // 5. Complete Payment
        Payment::create([
            'order_id' => $order->id,
            'patient_id' => $patient->id,
            'received_by' => $reception->id,
            'amount' => 100.00,
            'payment_type' => 'full',
            'payment_method' => 'card',
        ]);

        $order->updateAmounts();

        $this->assertEquals(150.00, $order->total_amount);
        $this->assertEquals(150.00, $order->paid_amount);
        $this->assertEquals(0.00, $order->remaining_amount);
        $this->assertEquals('paid', $order->payment_status);
    }

    public function test_appointment_scheduling()
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = Patient::factory()->create();
        
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        $this->assertEquals('scheduled', $appointment->status);
        
        $appointment->update(['status' => 'completed']);
        $this->assertEquals('completed', $appointment->fresh()->status);
    }

    public function test_patient_search_scope()
    {
        Patient::factory()->create(['name' => 'Alice Smith', 'phone' => '111111']);
        Patient::factory()->create(['name' => 'Bob Jones', 'phone' => '222222']);

        $results = Patient::search('Alice')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Alice Smith', $results->first()->name);

        $results = Patient::search('222222')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Bob Jones', $results->first()->name);
    }
}
