<?php

namespace App\Filament\Reception\Resources\OrderResource\Pages;

use App\Filament\Reception\Resources\OrderResource;
use App\Models\Appointment;
use App\Models\OrderItem;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'إنشاء طلب جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';
        $data['payment_status'] = 'not_paid';
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $order = $this->record;
        $formData = $this->form->getState();
        
        // If doctor is selected, create OrderItem and Appointment
        if (isset($formData['doctor_id']) && $formData['doctor_id']) {
            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'doctor_id' => $formData['doctor_id'],
                'status' => 'pending',
                'price' => 0, // Will be set later
                'notes' => $formData['notes'] ?? null,
            ]);

            // Create appointment with selected date (default to 9:00 AM if only date provided)
            $appointmentDate = isset($formData['appointment_date']) 
                ? \Carbon\Carbon::parse($formData['appointment_date'])->setTime(9, 0, 0)
                : now()->addDay()->setTime(9, 0, 0);

            Appointment::create([
                'patient_id' => $order->patient_id,
                'doctor_id' => $formData['doctor_id'],
                'order_item_id' => $orderItem->id,
                'appointment_date' => $appointmentDate,
                'status' => 'scheduled', // Auto-set to 'scheduled' (waiting status)
                'notes' => $formData['notes'] ?? null,
            ]);

            // Update order amounts
            $order->updateAmounts();
        }
    }
}

