<?php

namespace App\Filament\Doctor\Resources\PatientResource\Pages;

use App\Filament\Doctor\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected static ?string $title = 'عرض مريض';

    protected function getHeaderActions(): array
    {
        return [
            // Doctors can only view patients, not edit them
        ];
    }
}

