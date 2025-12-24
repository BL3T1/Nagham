<?php

namespace App\Filament\Reception\Pages;

use App\Models\Order;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProcessPayment extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string $view = 'filament.reception.pages.process-payment';

    protected static ?string $navigationLabel = 'معالجة الدفع';

    protected static ?string $title = 'معالجة الدفع';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->label('الطلب')
                    ->options(function () {
                        return Order::with('patient')
                            ->get()
                            ->mapWithKeys(fn (Order $order) => [
                                $order->id => "طلب #{$order->id} - {$order->patient->name} - " . __('messages.remaining_amount') . ": " . number_format($order->remaining_amount, 2) . " SYP"
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $order = Order::find($state);
                            if ($order) {
                                $set('patient_id', $order->patient_id);
                                $set('remaining_amount', $order->remaining_amount);
                            }
                        }
                    }),
                Forms\Components\Select::make('patient_id')
                    ->label('المريض')
                    ->options(function (Forms\Get $get) {
                        $orderId = $get('order_id');
                        if (!$orderId) {
                            return [];
                        }
                        $order = Order::find($orderId);
                        if (!$order) {
                            return [];
                        }
                        return [$order->patient_id => $order->patient->name];
                    })
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('remaining_amount')
                    ->label('المبلغ المتبقي')
                    ->prefix('SYP')
                    ->disabled()
                    ->dehydrated(false),
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
                    ->default(fn (Forms\Get $get) => $get('remaining_amount')),
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
            ])
            ->statePath('data');
    }

    public function process(): void
    {
        $data = $this->form->getState();
        $order = Order::find($data['order_id']);

        if (!$order) {
            Notification::make()
                ->title('الطلب غير موجود')
                ->danger()
                ->send();
            return;
        }

        // For full payment, use remaining amount
        if ($data['payment_type'] === 'full') {
            $data['amount'] = $order->remaining_amount;
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
        if ($data['amount'] > $order->remaining_amount) {
            Notification::make()
                ->title('مبلغ الدفع يتجاوز المبلغ المتبقي')
                ->danger()
                ->send();
            return;
        }

        // Check if order is already paid
        if ($order->isPaid() && $data['payment_type'] === 'full') {
            Notification::make()
                ->title('الطلب مدفوع بالكامل بالفعل')
                ->warning()
                ->send();
            return;
        }

        Payment::create([
            'order_id' => $order->id,
            'patient_id' => $order->patient_id,
            'received_by' => auth()->id(),
            'amount' => round((float) $data['amount'], 2),
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
        ]);

        $order->refresh();

        Notification::make()
            ->title('تمت معالجة الدفع بنجاح')
            ->success()
            ->send();

        $this->form->fill();
    }
}

