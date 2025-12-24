<?php

namespace App\Filament\Reception\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'المدفوعات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->required()
                    ->numeric()
                    ->prefix('SYP')
                    ->minValue(0.01)
                    ->step(0.01),
                Forms\Components\Select::make('payment_type')
                    ->label('نوع الدفع')
                    ->options([
                        'full' => 'دفع كامل',
                        'partial' => 'دفع جزئي',
                        'installment' => 'قسط',
                        'refund' => 'استرجاع',
                    ])
                    ->required()
                    ->default('full'),
                Forms\Components\Select::make('payment_method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash' => 'نقدي',
                        'card' => 'بطاقة',
                        'bank_transfer' => 'تحويل بنكي',
                        'other' => 'أخرى',
                    ])
                    ->required()
                    ->default('cash'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('نوع الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full' => 'دفع كامل',
                        'partial' => 'دفع جزئي',
                        'installment' => 'قسط',
                        'refund' => 'استرجاع',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'full' => 'success',
                        'partial' => 'info',
                        'installment' => 'warning',
                        'refund' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'نقدي',
                        'card' => 'بطاقة',
                        'bank_transfer' => 'تحويل بنكي',
                        'other' => 'أخرى',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('receiver.name')
                    ->label('استلم من')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_type')
                    ->label('نوع الدفع')
                    ->options([
                        'full' => 'دفع كامل',
                        'partial' => 'دفع جزئي',
                        'installment' => 'قسط',
                        'refund' => 'استرجاع',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['patient_id'] = $this->ownerRecord->patient_id;
                        $data['received_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

