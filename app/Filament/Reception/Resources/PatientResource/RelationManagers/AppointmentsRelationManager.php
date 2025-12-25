<?php

namespace App\Filament\Reception\Resources\PatientResource\RelationManagers;

use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'المواعيد';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('المريض')
                    ->relationship('patient', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('الهاتف')
                            ->required()
                            ->tel()
                            ->unique(\App\Models\Patient::class, 'phone')
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
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return \App\Models\Patient::create($data)->id;
                    })
                    ->default(fn ($record) => $record ? $record->patient_id : ($this->ownerRecord->id ?? null))
                    ->disabled(fn ($record) => $record === null && $this->ownerRecord !== null),
                Forms\Components\Select::make('doctor_id')
                    ->label('الطبيب')
                    ->relationship('doctor', 'name', fn ($query) => $query->where('role', 'doctor')->where('is_active', true))
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('appointment_date')
                    ->label('تاريخ الموعد')
                    ->required()
                    ->default(fn ($record) => $record ? $record->appointment_date?->format('Y-m-d') : now()->addDay()->format('Y-m-d'))
                    ->live()
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->helperText(function (Forms\Get $get) {
                        if ($get('appointment_date')) {
                            try {
                                $date = \Carbon\Carbon::parse($get('appointment_date'));
                                $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                                $dayName = $days[$date->dayOfWeek];
                                return 'يوم الموعد: ' . $dayName;
                            } catch (\Exception $e) {
                                return '';
                            }
                        }
                        return '';
                    }),
                Forms\Components\Hidden::make('status')
                    ->default('scheduled')
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('الطبيب')
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
                Tables\Filters\Filter::make('tomorrow')
                    ->label('مواعيد الغد')
                    ->query(fn (Builder $query) => $query->tomorrow())
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة موعد')
                    ->mutateFormDataBeforeCreate(function (array $data): array {
                        // Set patient_id from owner record if not set
                        if (!isset($data['patient_id']) && $this->ownerRecord) {
                            $data['patient_id'] = $this->ownerRecord->id;
                        }
                        // Try to get doctor_id from order_item if not set
                        if ((!isset($data['doctor_id']) || empty($data['doctor_id'])) && isset($data['order_item_id'])) {
                            $orderItem = \App\Models\OrderItem::find($data['order_item_id']);
                            if ($orderItem && $orderItem->doctor_id) {
                                $data['doctor_id'] = $orderItem->doctor_id;
                            }
                        }
                        // Ensure doctor_id is set (required field)
                        if (!isset($data['doctor_id']) || empty($data['doctor_id'])) {
                            throw new \Exception('يجب اختيار الطبيب');
                        }
                        // Set appointment_date to start of day (default time)
                        if (isset($data['appointment_date'])) {
                            $date = \Carbon\Carbon::parse($data['appointment_date']);
                            $data['appointment_date'] = $date->setTime(9, 0, 0); // Default to 9:00 AM
                        }
                        // Ensure status is set to 'scheduled' (waiting)
                        $data['status'] = 'scheduled';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->mutateFormDataBeforeSave(function (array $data): array {
                        // Set appointment_date to start of day if only date is provided
                        if (isset($data['appointment_date'])) {
                            $date = \Carbon\Carbon::parse($data['appointment_date']);
                            // If it's just a date (no time), set default time to 9:00 AM
                            if ($date->format('H:i') === '00:00') {
                                $data['appointment_date'] = $date->setTime(9, 0, 0);
                            }
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('appointment_date', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->tomorrow());
    }
}

