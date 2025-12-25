<?php

namespace App\Filament\Reception\Resources\OrderResource\Actions;

use App\Models\Order;
use App\Models\Payment;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class ProcessPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'processPayment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('معالجة الدفع')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->form([
                Forms\Components\Select::make('order_item_id')
                    ->label('الجلسة (الطبيب)')
                    ->options(function (Order $record) {
                        return $record->orderItems()
                            ->with('doctor')
                            ->get()
                            ->mapWithKeys(fn ($item) => [
                                $item->id => "{$item->doctor->name} - " . number_format($item->remaining_amount, 2) . " SYP متبقي"
                            ]);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Order $record) {
                        if ($state) {
                            $orderItem = $record->orderItems()->find($state);
                            if ($orderItem) {
                                $set('amount', $orderItem->remaining_amount);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->prefix('SYP')
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3),
            ])
            ->action(function (Order $record, array $data) {
                if (!isset($data['order_item_id'])) {
                    Notification::make()
                        ->title('يرجى اختيار الجلسة (الطبيب)')
                        ->danger()
                        ->send();
                    return;
                }

                $orderItem = $record->orderItems()->find($data['order_item_id']);
                if (!$orderItem) {
                    Notification::make()
                        ->title('عنصر الجلسة غير موجود')
                        ->danger()
                        ->send();
                    return;
                }

                // Validate amount
                if (!isset($data['amount']) || $data['amount'] <= 0) {
                    Notification::make()
                        ->title('مبلغ الدفع غير صالح')
                        ->danger()
                        ->send();
                    return;
                }

                // Validate amount doesn't exceed remaining for this order item
                $remainingForItem = $orderItem->remaining_amount;
                if ($data['amount'] > $remainingForItem) {
                    Notification::make()
                        ->title('مبلغ الدفع يتجاوز المبلغ المتبقي لهذا الطبيب')
                        ->danger()
                        ->send();
                    return;
                }

                Payment::create([
                    'order_id' => $record->id,
                    'order_item_id' => $orderItem->id,
                    'patient_id' => $record->patient_id,
                    'received_by' => auth()->id(),
                    'amount' => round((float) $data['amount'], 2),
                    'payment_type' => 'partial',
                    'payment_method' => 'cash',
                    'notes' => $data['notes'] ?? null,
                ]);

                $record->updateAmounts();

                Notification::make()
                    ->title('تمت معالجة الدفع بنجاح')
                    ->success()
                    ->send();
            })
            ->visible(fn (Order $record) => $record->remaining_amount > 0);
    }
}

