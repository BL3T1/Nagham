<?php

namespace App\Filament\Doctor\Pages;

use App\Models\Appointment;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SessionManagementPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.doctor.pages.session-management';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = 'إدارة الجلسات';

    public static function getNavigationLabel(): string
    {
        return __('messages.session_management');
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
                Forms\Components\Select::make('session_id')
                    ->label(__('messages.session'))
                    ->options(function () {
                        return OrderItem::where('doctor_id', auth()->id())
                            ->where('status', '!=', 'completed')
                            ->with('order.patient')
                            ->get()
                            ->mapWithKeys(fn ($item) => [
                                $item->id => __('messages.order') . " #{$item->order_id} - {$item->order->patient->name} (" . __("messages.{$item->status}") . ")"
                            ]);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $session = OrderItem::find($state);
                            if ($session) {
                                $set('price', $session->price ?? 0);
                                $set('eligible_for_installment', $session->eligible_for_installment ?? false);
                                $set('down_payment', $session->down_payment ?? 0);
                                $set('next_session_date', $session->next_session_date?->format('Y-m-d'));
                                $set('notes', $session->notes ?? '');
                            }
                        }
                    }),
            Forms\Components\TextInput::make('price')
                ->label(__('messages.session_price'))
                ->numeric()
                ->required()
                ->minValue(0),
            Forms\Components\Toggle::make('eligible_for_installment')
                ->label(__('messages.eligible_for_installment')),
            Forms\Components\TextInput::make('down_payment')
                ->label(__('messages.down_payment'))
                ->numeric()
                ->minValue(0)
                ->visible(fn (callable $get) => $get('eligible_for_installment')),
            Forms\Components\DatePicker::make('next_session_date')
                ->label(__('messages.next_session_date'))
                ->native(false),
                Forms\Components\Textarea::make('notes')
                    ->label(__('messages.notes'))
                    ->rows(3),
            ])
            ->statePath('data');
    }

    public function setPrice(): void
    {
        $data = $this->form->getState();
        
        if (!$data['session_id']) {
            Notification::make()
                ->title(__('messages.please_select_session'))
                ->warning()
                ->send();
            return;
        }

        $session = OrderItem::find($data['session_id']);
        
        if (!$session) {
            Notification::make()
                ->title(__('messages.session_not_found'))
                ->danger()
                ->send();
            return;
        }

        try {
            $session->update(['price' => $data['price']]);
            Notification::make()
                ->title(__('messages.price_updated_successfully'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_updating_price'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function setInstallment(): void
    {
        $data = $this->form->getState();
        
        if (!$data['session_id']) {
            Notification::make()
                ->title(__('messages.please_select_session'))
                ->warning()
                ->send();
            return;
        }

        $session = OrderItem::find($data['session_id']);
        
        if (!$session) {
            return;
        }

        try {
            $session->update([
                'eligible_for_installment' => $data['eligible_for_installment'] ?? false,
                'down_payment' => $data['down_payment'] ?? 0,
                'price' => $data['price'] ?? $session->price,
            ]);
            Notification::make()
                ->title(__('messages.installment_eligibility_updated'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_updating_installment_eligibility'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function scheduleNextSession(): void
    {
        $data = $this->form->getState();
        
        if (!$data['session_id']) {
            Notification::make()
                ->title(__('messages.please_select_session'))
                ->warning()
                ->send();
            return;
        }

        $session = OrderItem::find($data['session_id']);
        
        if (!$session || !$data['next_session_date']) {
            Notification::make()
                ->title(__('messages.please_select_date'))
                ->warning()
                ->send();
            return;
        }

        try {
            $session->update(['next_session_date' => $data['next_session_date']]);
            
            // Create appointment if order item is linked
            if ($session->order->patient_id) {
                Appointment::create([
                    'patient_id' => $session->order->patient_id,
                    'doctor_id' => $session->doctor_id,
                    'order_item_id' => $session->id,
                    'appointment_date' => $data['next_session_date'],
                    'status' => 'scheduled',
                    'notes' => $data['notes'] ?? null,
                ]);
            }
            
            Notification::make()
                ->title(__('messages.next_session_scheduled_successfully'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_scheduling_next_session'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function endSession(): void
    {
        $data = $this->form->getState();
        
        if (!$data['session_id']) {
            Notification::make()
                ->title(__('messages.please_select_session'))
                ->warning()
                ->send();
            return;
        }

        $session = OrderItem::find($data['session_id']);
        
        if (!$session) {
            Notification::make()
                ->title(__('messages.session_not_found'))
                ->danger()
                ->send();
            return;
        }

        try {
            $session->update([
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);
            
            Notification::make()
                ->title(__('messages.session_ended_successfully'))
                ->success()
                ->send();
            
            $this->form->fill();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_ending_session'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

