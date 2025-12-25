<?php

namespace App\Filament\Reception\Pages;

use App\Models\Appointment;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TomorrowAppointments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.reception.pages.tomorrow-appointments';

    protected static ?string $navigationLabel = 'مواعيد الغد';

    protected static ?string $title = 'مواعيد الغد';

    public function getBreadcrumbs(): array
    {
        return [
            url('/reception') => 'لوحة التحكم',
            '' => 'مواعيد الغد',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Appointment::query()->whereDate('appointment_date', now()->addDay()))
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('المريض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('الطبيب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointment_date')
                    ->label('تاريخ الموعد')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'مجدول',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('الطبيب')
                    ->relationship('doctor', 'name', fn ($query) => $query->where('role', 'doctor'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'scheduled' => 'مجدول',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
            ])
            ->defaultSort('appointment_date', 'asc')
            ->emptyStateHeading('لا توجد مواعيد للغد')
            ->emptyStateDescription('لا توجد مواعيد مجدولة ليوم الغد');
    }
}

