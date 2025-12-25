<?php

namespace App\Filament\Admin\Resources\AppointmentResource\Pages;

use App\Filament\Admin\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected static ?string $title = 'إنشاء موعد جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure doctor_id is set (required field)
        if (!isset($data['doctor_id']) || empty($data['doctor_id'])) {
            throw new \Exception('يجب اختيار الطبيب');
        }
        
        return $data;
    }
}
