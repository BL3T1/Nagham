<?php

namespace App\Filament\Reception\Resources;

use App\Filament\Reception\Resources\PatientResource\Pages;
use App\Filament\Reception\Resources\PatientResource\RelationManagers\AppointmentsRelationManager;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'المرضى';

    public static function getModelLabel(): string
    {
        return 'مريض';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المرضى';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('الهاتف')
                    ->required()
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date_of_birth')
                    ->label('تاريخ الميلاد'),
                Forms\Components\Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),
                Forms\Components\Textarea::make('address')
                    ->label('العنوان')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('medical_history')
                    ->label('التاريخ الطبي')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('age')
                    ->label('العمر')
                    ->getStateUsing(fn (Patient $record) => $record->age),
                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('الطلبات'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),
                Tables\Filters\Filter::make('appointment_date')
                    ->label('تاريخ الموعد')
                    ->form([
                        Forms\Components\DatePicker::make('appointment_date')
                            ->label('تاريخ الموعد'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['appointment_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('appointments', function ($q) use ($date) {
                                    $q->whereDate('appointment_date', $date);
                                })
                            );
                    }),
                Tables\Filters\Filter::make('appointment_date_range')
                    ->label('نطاق تاريخ الموعد')
                    ->form([
                        Forms\Components\DatePicker::make('appointment_date_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('appointment_date_to')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['appointment_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereHas('appointments', function ($q) use ($date) {
                                    $q->whereDate('appointment_date', '>=', $date);
                                })
                            )
                            ->when(
                                $data['appointment_date_to'],
                                fn (Builder $query, $date): Builder => $query->whereHas('appointments', function ($q) use ($date) {
                                    $q->whereDate('appointment_date', '<=', $date);
                                })
                            );
                    }),
                Tables\Filters\Filter::make('appointment_today')
                    ->label('مواعيد اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereHas('appointments', function ($q) {
                        $q->whereDate('appointment_date', today());
                    })),
                Tables\Filters\Filter::make('appointment_tomorrow')
                    ->label('مواعيد الغد')
                    ->query(fn (Builder $query): Builder => $query->whereHas('appointments', function ($q) {
                        $q->whereDate('appointment_date', now()->addDay());
                    })),
                Tables\Filters\Filter::make('appointment_upcoming')
                    ->label('المواعيد القادمة')
                    ->query(fn (Builder $query): Builder => $query->whereHas('appointments', function ($q) {
                        $q->where('appointment_date', '>=', now());
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            AppointmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}

