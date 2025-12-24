<?php

namespace App\Filament\Reception\Pages;

use App\Models\Appointment;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Appointments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.reception.pages.appointments';

    protected static ?string $navigationLabel = 'المواعيد';

    protected static ?string $title = 'المواعيد';

    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(Appointment::query()->with(['patient', 'doctor', 'orderItem']))
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
                        'confirmed' => 'مؤكد',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        'no_show' => 'لم يحضر',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'confirmed' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        'no_show' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('orderItem.order.id')
                    ->label('رقم الطلب')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'scheduled' => 'مجدول',
                        'confirmed' => 'مؤكد',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        'no_show' => 'لم يحضر',
                    ]),
                Tables\Filters\Filter::make('upcoming')
                    ->label('القادمة')
                    ->query(fn (Builder $query) => $query->where('appointment_date', '>=', now())),
            ])
            ->defaultSort('appointment_date', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}

