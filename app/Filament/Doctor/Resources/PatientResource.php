<?php

namespace App\Filament\Doctor\Resources;

use App\Filament\Doctor\Resources\PatientResource\Pages;
use App\Filament\Doctor\Resources\PatientResource\RelationManagers;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'المرضى';
    
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'مريض';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المرضى';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('appointments', function ($query) {
                $query->where('doctor_id', auth()->id());
            })
            ->with(['appointments' => function ($query) {
                $query->where('doctor_id', auth()->id());
            }]);
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
                    ->tel()
                    ->required()
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
                Tables\Columns\TextColumn::make('appointments_count')
                    ->counts('appointments')
                    ->label('المواعيد'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
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
                                    $q->where('doctor_id', auth()->id())
                                      ->whereDate('appointment_date', $date);
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
                                    $q->where('doctor_id', auth()->id())
                                      ->whereDate('appointment_date', '>=', $date);
                                })
                            )
                            ->when(
                                $data['appointment_date_to'],
                                fn (Builder $query, $date): Builder => $query->whereHas('appointments', function ($q) use ($date) {
                                    $q->where('doctor_id', auth()->id())
                                      ->whereDate('appointment_date', '<=', $date);
                                })
                            );
                    }),
                Tables\Filters\Filter::make('appointment_today')
                    ->label('مواعيد اليوم')
                    ->query(fn (Builder $query): Builder => $query->whereHas('appointments', function ($q) {
                        $q->where('doctor_id', auth()->id())
                          ->whereDate('appointment_date', today());
                    })),
                Tables\Filters\Filter::make('appointment_tomorrow')
                    ->label('مواعيد الغد')
                    ->query(fn (Builder $query): Builder => $query->whereHas('appointments', function ($q) {
                        $q->where('doctor_id', auth()->id())
                          ->whereDate('appointment_date', now()->addDay());
                    })),
                Tables\Filters\Filter::make('appointment_upcoming')
                    ->label('المواعيد القادمة')
                    ->query(fn (Builder $query): Builder => $query->whereHas('appointments', function ($q) {
                        $q->where('doctor_id', auth()->id())
                          ->where('appointment_date', '>=', now());
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('الاسم'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('الهاتف'),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->label('تاريخ الميلاد')
                            ->date(),
                        Infolists\Components\TextEntry::make('age')
                            ->label('العمر')
                            ->getStateUsing(fn (Patient $record) => $record->age),
                        Infolists\Components\TextEntry::make('gender')
                            ->label('الجنس')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'male' => 'ذكر',
                                'female' => 'أنثى',
                                default => $state,
                            })
                            ->badge(),
                        Infolists\Components\TextEntry::make('address')
                            ->label('العنوان')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('medical_history')
                            ->label('التاريخ الطبي')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'view' => Pages\ViewPatient::route('/{record}'),
        ];
    }
}
