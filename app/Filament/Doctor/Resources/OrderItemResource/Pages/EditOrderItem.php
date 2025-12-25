<?php

namespace App\Filament\Doctor\Resources\OrderItemResource\Pages;

use App\Filament\Doctor\Resources\OrderItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrderItem extends EditRecord
{
    protected static string $resource = OrderItemResource::class;

    protected static ?string $title = 'تعديل جلسة';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}

