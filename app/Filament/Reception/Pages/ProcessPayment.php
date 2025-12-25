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

    public function getBreadcrumbs(): array
    {
        return [
            url('/reception') => 'لوحة التحكم',
            '' => 'معالجة الدفع',
        ];
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('المريض')
                    ->options(function () {
                        return \App\Models\Patient::pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $patient = \App\Models\Patient::find($state);
                            if ($patient) {
                                // Calculate total due amount from patient's unpaid orders
                                $totalDue = $patient->total_due_amount;
                                $set('calculated_amount', $totalDue);
                            }
                        } else {
                            $set('calculated_amount', 0);
                            $set('appointment_id', null);
                        }
                    }),
                Forms\Components\Select::make('appointment_id')
                    ->label('الموعد')
                    ->options(function (Forms\Get $get) {
                        $patientId = $get('patient_id');
                        if (!$patientId) {
                            return [];
                        }
                        return \App\Models\Appointment::where('patient_id', $patientId)
                            ->with(['doctor', 'orderItem.order'])
                            ->get()
                            ->mapWithKeys(fn ($appointment) => [
                                $appointment->id => "موعد #{$appointment->id} - {$appointment->doctor->name} - " . 
                                    ($appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d H:i') : '') .
                                    ($appointment->orderItem && $appointment->orderItem->order ? 
                                        " - جلسة #{$appointment->orderItem->order->id}" : '')
                            ]);
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->visible(fn (Forms\Get $get) => $get('patient_id'))
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        if ($state) {
                            $appointment = \App\Models\Appointment::find($state);
                            if ($appointment && $appointment->orderItem && $appointment->orderItem->order) {
                                $orderItem = $appointment->orderItem;
                                $order = $orderItem->order;
                                $order->updateAmounts();
                                $set('order_id', $order->id);
                                $set('order_item_id', $orderItem->id);
                                // Calculate remaining amount for this specific order item (doctor)
                                $set('amount_left', $orderItem->remaining_amount);
                                $set('calculated_amount', $orderItem->remaining_amount);
                            }
                        }
                    }),
                Forms\Components\Hidden::make('order_id')
                    ->dehydrated(),
                Forms\Components\Hidden::make('order_item_id')
                    ->dehydrated(),
                Forms\Components\TextInput::make('order_item_price')
                    ->label('سعر الجلسة')
                    ->prefix('SYP')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Forms\Get $get) => $get('appointment_id'))
                    ->formatStateUsing(function (Forms\Get $get) {
                        $appointmentId = $get('appointment_id');
                        if ($appointmentId) {
                            $appointment = \App\Models\Appointment::find($appointmentId);
                            if ($appointment && $appointment->orderItem) {
                                return number_format($appointment->orderItem->price ?? 0, 2);
                            }
                        }
                        return '0.00';
                    }),
                Forms\Components\TextInput::make('amount_left')
                    ->label('المبلغ المتبقي للطبيب')
                    ->prefix('SYP')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Forms\Get $get) => $get('appointment_id'))
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->prefix('SYP')
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->visible(fn (Forms\Get $get) => $get('appointment_id'))
                    ->default(fn (Forms\Get $get) => $get('amount_left'))
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $amountLeft = $get('amount_left');
                        if ($state && $amountLeft) {
                            $newRemaining = max(0, $amountLeft - $state);
                            $set('new_remaining', $newRemaining);
                        }
                    }),
                Forms\Components\TextInput::make('new_remaining')
                    ->label('المبلغ المتبقي بعد الدفع')
                    ->prefix('SYP')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Forms\Get $get) => $get('appointment_id') && $get('amount'))
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2)),
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

        if (!isset($data['patient_id']) || !isset($data['appointment_id'])) {
            Notification::make()
                ->title('يرجى اختيار المريض والموعد')
                ->danger()
                ->send();
            return;
        }

        $appointment = \App\Models\Appointment::find($data['appointment_id']);
        if (!$appointment || !$appointment->orderItem || !$appointment->orderItem->order) {
            Notification::make()
                ->title('الموعد غير مرتبط بجلسة')
                ->danger()
                ->send();
            return;
        }

        $orderItem = $appointment->orderItem;
        $order = $orderItem->order;

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
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'patient_id' => $data['patient_id'],
            'received_by' => auth()->id(),
            'amount' => round((float) $data['amount'], 2),
            'payment_type' => 'partial', // Always partial since we're paying specific amounts
            'payment_method' => 'cash',
            'notes' => $data['notes'] ?? null,
        ]);

        // Update order amounts (which aggregates all payments)
        $order->updateAmounts();

        Notification::make()
            ->title('تمت معالجة الدفع بنجاح')
            ->success()
            ->send();

        $this->form->fill();
    }
}

