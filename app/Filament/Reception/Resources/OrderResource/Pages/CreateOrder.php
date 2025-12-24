<?php

namespace App\Filament\Reception\Resources\OrderResource\Pages;

use App\Filament\Reception\Resources\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'إنشاء طلب جديد';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'pending';
        $data['payment_status'] = 'pending';
        
        return $data;
    }
}

