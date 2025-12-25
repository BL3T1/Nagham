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
                Forms\Components\Select::make('order_item_id')
                    ->label('الجلسة (الطبيب)')
                    ->options(function () {
                        $order = $this->ownerRecord;
                        return $order->orderItems()
                            ->with('doctor')
                            ->get()
                            ->mapWithKeys(fn ($item) => [
                                $item->id => "{$item->doctor->name} - " . number_format($item->remaining_amount, 2) . " SYP متبقي"
                            ]);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $orderItem = \App\Models\OrderItem::find($state);
                            if ($orderItem) {
                                $set('amount', $orderItem->remaining_amount);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ المدفوع')
                    ->required()
                    ->numeric()
                    ->prefix('SYP')
                    ->minValue(0.01)
                    ->step(0.01),
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
                Tables\Columns\TextColumn::make('orderItem.doctor.name')
                    ->label('الطبيب')
                    ->sortable()
                    ->toggleable(),
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
                    ->label('إضافة دفعة')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['order_id'] = $this->ownerRecord->id;
                        $data['patient_id'] = $this->ownerRecord->patient_id;
                        $data['received_by'] = auth()->id();
                        $data['payment_type'] = 'partial';
                        $data['payment_method'] = 'cash';
                        return $data;
                    })
                    ->after(function () {
                        $this->ownerRecord->updateAmounts();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

