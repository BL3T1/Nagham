<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Filament\Admin\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationLabel = 'المدفوعات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->label('رقم الطلب')
                    ->relationship('order', 'id')
                    ->required(),
                Forms\Components\Select::make('patient_id')
                    ->label('المريض')
                    ->relationship('patient', 'name')
                    ->required(),
                Forms\Components\Select::make('received_by')
                    ->label('استلم من')
                    ->relationship('receiver', 'name')
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->prefix('SYP'),
                Forms\Components\Select::make('payment_type')
                    ->label('نوع الدفع')
                    ->options([
                        'full' => 'كامل',
                        'partial' => 'جزئي',
                        'installment' => 'قسط',
                        'refund' => 'استرجاع',
                    ])
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash' => 'نقدي',
                        'card' => 'بطاقة',
                        'bank_transfer' => 'تحويل بنكي',
                        'other' => 'أخرى',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->label('رقم الطلب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('المريض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('استلم من')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('نوع الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full' => 'كامل',
                        'partial' => 'جزئي',
                        'installment' => 'قسط',
                        'refund' => 'استرجاع',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'نقدي',
                        'card' => 'بطاقة',
                        'bank_transfer' => 'تحويل بنكي',
                        'other' => 'أخرى',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
