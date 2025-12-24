<?php

namespace App\Filament\Doctor\Resources\OrderItemResource\Pages;

use App\Filament\Doctor\Resources\OrderItemResource;
use Filament\Resources\Pages\ListRecords;

class ListOrderItems extends ListRecords
{
    protected static string $resource = OrderItemResource::class;

    protected static ?string $title = 'جلساتي';
}

