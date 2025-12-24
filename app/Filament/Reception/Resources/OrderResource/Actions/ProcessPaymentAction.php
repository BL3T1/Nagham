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
                Forms\Components\Select::make('payment_type')
                    ->label('نوع الدفع')
                    ->options([
                        'full' => 'دفع كامل',
                        'partial' => 'دفع جزئي',
                        'installment' => 'قسط',
                    ])
                    ->required()
                    ->default('full')
                    ->live(),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->prefix('SYP')
                    ->required(fn (Forms\Get $get) => in_array($get('payment_type'), ['partial', 'installment']))
                    ->minValue(0.01)
                    ->step(0.01)
                    ->visible(fn (Forms\Get $get) => in_array($get('payment_type'), ['partial', 'installment']))
                    ->default(fn (Order $record) => $record->remaining_amount),
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
                    ->rows(3),
            ])
            ->action(function (Order $record, array $data) {
                // For full payment, use remaining amount
                if ($data['payment_type'] === 'full') {
                    $data['amount'] = $record->remaining_amount;
                }

                // Validate amount
                if (!isset($data['amount']) || $data['amount'] <= 0) {
                    Notification::make()
                        ->title('مبلغ الدفع غير صالح')
                        ->danger()
                        ->send();
                    return;
                }

                // Validate amount doesn't exceed remaining
                if ($data['amount'] > $record->remaining_amount) {
                    Notification::make()
                        ->title('مبلغ الدفع يتجاوز المبلغ المتبقي')
                        ->danger()
                        ->send();
                    return;
                }

                // Check if order is already paid
                if ($record->isPaid() && $data['payment_type'] === 'full') {
                    Notification::make()
                        ->title('الطلب مدفوع بالكامل بالفعل')
                        ->warning()
                        ->send();
                    return;
                }

                Payment::create([
                    'order_id' => $record->id,
                    'patient_id' => $record->patient_id,
                    'received_by' => auth()->id(),
                    'amount' => round((float) $data['amount'], 2),
                    'payment_type' => $data['payment_type'],
                    'payment_method' => $data['payment_method'],
                    'notes' => $data['notes'] ?? null,
                ]);

                $record->refresh();

                Notification::make()
                    ->title('تمت معالجة الدفع بنجاح')
                    ->success()
                    ->send();
            })
            ->visible(fn (Order $record) => $record->remaining_amount > 0);
    }
}

