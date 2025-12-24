<?php

namespace App\Filament\Doctor\Pages;

use App\Models\OrderItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class TodayPatientsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'filament.doctor.pages.today-patients';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = 'مرضى اليوم';

    public static function getNavigationLabel(): string
    {
        return __('messages.todays_patients');
    }

    public function mount(): void
    {
        //
    }

    public function getSessionsProperty()
    {
        return OrderItem::where('doctor_id', auth()->id())
            ->whereDate('next_session_date', Carbon::today())
            ->where('status', '!=', 'completed')
            ->with(['order.patient', 'doctor'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'patient_name' => $item->order->patient->name ?? 'N/A',
                    'order_id' => $item->order_id,
                    'status' => $item->status,
                    'price' => $item->price,
                    'next_session_date' => $item->next_session_date?->format('Y-m-d H:i'),
                ];
            });
    }

    public function getCountProperty()
    {
        return OrderItem::where('doctor_id', auth()->id())
            ->whereDate('next_session_date', Carbon::today())
            ->where('status', '!=', 'completed')
            ->count();
    }

    public function startSession($sessionId): void
    {
        $session = OrderItem::find($sessionId);
        
        if (!$session) {
            Notification::make()
                ->title(__('messages.session_not_found'))
                ->danger()
                ->send();
            return;
        }

        try {
            $session->update(['status' => 'in_progress']);
            
            Notification::make()
                ->title(__('messages.session_started_successfully'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_starting_session'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function endSession($sessionId): void
    {
        $session = OrderItem::find($sessionId);
        
        if (!$session) {
            Notification::make()
                ->title(__('messages.session_not_found'))
                ->danger()
                ->send();
            return;
        }

        try {
            $session->update(['status' => 'completed']);
            
            Notification::make()
                ->title(__('messages.session_ended_successfully'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_ending_session'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

