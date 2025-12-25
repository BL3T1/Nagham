<?php

namespace App\Filament\Doctor\Resources\AppointmentResource\Pages;

use App\Filament\Doctor\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected static ?string $title = 'إنشاء موعد جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure doctor_id is always set to the authenticated doctor
        if (!isset($data['doctor_id']) || empty($data['doctor_id'])) {
            $data['doctor_id'] = auth()->id();
        }
        
        // Remove order_item_id if set (doctors don't need to select order items)
        unset($data['order_item_id']);
        
        return $data;
    }
}
