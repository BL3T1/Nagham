<?php

namespace App\Filament\Reception\Resources\OrderResource\Pages;

use App\Filament\Reception\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'الطلبات';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة طلب'),
        ];
    }
}

