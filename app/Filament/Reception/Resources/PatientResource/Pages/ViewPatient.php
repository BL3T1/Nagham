<?php

namespace App\Filament\Reception\Resources\PatientResource\Pages;

use App\Filament\Reception\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected static ?string $title = 'عرض مريض';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

