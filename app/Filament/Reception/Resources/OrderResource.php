<?php

namespace App\Filament\Reception\Resources;

use App\Filament\Reception\Resources\OrderResource\Actions\ProcessPaymentAction;
use App\Filament\Reception\Resources\OrderResource\Pages;
use App\Filament\Reception\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'الطلبات';

    public static function getModelLabel(): string
    {
        return 'طلب';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الطلبات';
    }

    public static function form(Form $form): Form
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
                    ->live(),
                Forms\Components\Select::make('doctor_id')
                    ->label('الطبيب')
                    ->options(function (Forms\Get $get) {
                        return \App\Models\User::where('role', 'doctor')
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get) => $get('patient_id'))
                    ->live(),
                Forms\Components\DatePicker::make('appointment_date')
                    ->label('تاريخ الموعد')
                    ->required()
                    ->default(now()->addDay())
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->visible(fn (Forms\Get $get) => $get('patient_id') && $get('doctor_id'))
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
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'paid' => 'مدفوع',
                        'not_paid' => 'غير مدفوع',
                    ])
                    ->required()
                    ->default('not_paid')
                    ->disabled()
                    ->visible(fn () => $form->getRecord()),
                Forms\Components\TextInput::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->numeric()
                    ->prefix('SYP')
                    ->disabled()
                    ->visible(fn () => $form->getRecord()),
                Forms\Components\TextInput::make('paid_amount')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->prefix('SYP')
                    ->disabled()
                    ->visible(fn () => $form->getRecord()),
                Forms\Components\TextInput::make('remaining_amount')
                    ->label('المبلغ المتبقي')
                    ->numeric()
                    ->prefix('SYP')
                    ->disabled()
                    ->visible(fn () => $form->getRecord()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم الطلب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('المريض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'مدفوع',
                        'not_paid' => 'غير مدفوع',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'not_paid' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المبلغ المتبقي')
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'paid' => 'مدفوع',
                        'not_paid' => 'غير مدفوع',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                ProcessPaymentAction::make(),
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
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}

