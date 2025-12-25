<?php

namespace App\Filament\Doctor\Resources;

use App\Filament\Doctor\Resources\AppointmentResource\Pages;
use App\Filament\Doctor\Resources\AppointmentResource\RelationManagers;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'المواعيد';
    
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'موعد';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المواعيد';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('doctor_id', auth()->id())
            ->with(['patient', 'doctor', 'orderItem']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('المريض')
                    ->relationship(
                        'patient',
                        'name',
                        fn ($query) => $query->where(function ($q) {
                            $doctorId = auth()->id();
                            // Patients who have appointments with this doctor OR
                            // Patients who have order items with this doctor
                            $q->whereHas('appointments', function ($appointmentQuery) use ($doctorId) {
                                $appointmentQuery->where('doctor_id', $doctorId);
                            })
                            ->orWhereHas('orders.orderItems', function ($orderItemQuery) use ($doctorId) {
                                $orderItemQuery->where('doctor_id', $doctorId);
                            });
                        })
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\Select::make('doctor_id')
                    ->label('الطبيب')
                    ->relationship('doctor', 'name', fn ($query) => $query->where('role', 'doctor'))
                    ->required()
                    ->default(auth()->id())
                    ->disabled(),
                Forms\Components\DateTimePicker::make('appointment_date')
                    ->label('تاريخ الموعد')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'scheduled' => 'مجدول',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->required()
                    ->default('scheduled'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('المريض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointment_date')
                    ->label('تاريخ الموعد')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $date = \Carbon\Carbon::parse($state);
                        $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                        $dayName = $days[$date->dayOfWeek];
                        return $dayName . ' - ' . $date->format('Y-m-d H:i');
                    })
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
                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'scheduled' => 'مجدول',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('مواعيد اليوم')
                    ->query(fn (Builder $query) => $query->today())
                    ->default(),
                Tables\Filters\Filter::make('tomorrow')
                    ->label('مواعيد الغد')
                    ->query(fn (Builder $query) => $query->tomorrow()),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->today())
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->striped()
            ->defaultSort('appointment_date', 'asc')
            ->emptyStateHeading('لا توجد مواعيد لليوم')
            ->emptyStateDescription('لا توجد مواعيد مجدولة لليوم')
            ->emptyStateIcon('heroicon-o-calendar');
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
