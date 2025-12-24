<?php

namespace App\Filament\Reception\Resources;

use App\Filament\Reception\Resources\OrderResource\Actions\ProcessPaymentAction;
use App\Filament\Reception\Resources\OrderResource\Pages;
use App\Filament\Reception\Resources\OrderResource\RelationManagers\OrderItemsRelationManager;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('المريض')
                    ->relationship('patient', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'partial' => 'جزئي',
                        'paid' => 'مدفوع',
                    ])
                    ->required()
                    ->default('pending')
                    ->disabled(),
                Forms\Components\TextInput::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->numeric()
                    ->prefix('SYP')
                    ->disabled(),
                Forms\Components\TextInput::make('paid_amount')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->prefix('SYP')
                    ->disabled(),
                Forms\Components\TextInput::make('remaining_amount')
                    ->label('المبلغ المتبقي')
                    ->numeric()
                    ->prefix('SYP')
                    ->disabled(),
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
                        'pending' => 'قيد الانتظار',
                        'partial' => 'جزئي',
                        'paid' => 'مدفوع',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'partial' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('المبلغ المدفوع')
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('المبلغ المتبقي')
                    ->formatStateUsing(fn ($state): string => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderItems_count')
                    ->counts('orderItems')
                    ->label('الجلسات'),
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
                        'pending' => 'قيد الانتظار',
                        'partial' => 'جزئي',
                        'paid' => 'مدفوع',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            OrderItemsRelationManager::class,
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

