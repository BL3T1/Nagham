<?php

namespace App\Filament\Reception\Resources\PatientResource\Pages;

use App\Filament\Reception\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected static ?string $title = 'المرضى';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مريض'),
        ];
    }
}

