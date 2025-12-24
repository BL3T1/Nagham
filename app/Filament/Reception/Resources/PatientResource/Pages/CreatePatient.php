<?php

namespace App\Filament\Reception\Resources\PatientResource\Pages;

use App\Filament\Reception\Resources\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected static ?string $title = 'إنشاء مريض جديد';
}

