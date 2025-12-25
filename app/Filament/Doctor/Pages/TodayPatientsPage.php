<?php

namespace App\Filament\Doctor\Pages;

use App\Filament\Doctor\Resources\PatientResource;
use App\Models\OrderItem;
use App\Models\Patient;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TodayPatientsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'filament.doctor.pages.today-patients';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = 'مرضى اليوم';

    public static function getNavigationLabel(): string
    {
        return __('messages.todays_patients');
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/doctor') => 'لوحة التحكم',
            '' => 'مرضى اليوم',
        ];
    }

    public function mount(): void
    {
        //
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getPatientsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('age')
                    ->label('العمر')
                    ->getStateUsing(fn (Patient $record) => $record->age),
                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('today_sessions_count')
                    ->label('جلسات اليوم')
                    ->getStateUsing(function (Patient $record) {
                        $doctorId = auth()->id();
                        // Count order items scheduled for today
                        $orderItemsCount = OrderItem::whereHas('order', function (Builder $query) use ($record) {
                            $query->where('patient_id', $record->id);
                        })
                            ->where('doctor_id', $doctorId)
                            ->whereDate('next_session_date', Carbon::today())
                            ->where('status', '!=', 'completed')
                            ->count();
                        // Count appointments scheduled for today
                        $appointmentsCount = \App\Models\Appointment::where('patient_id', $record->id)
                            ->where('doctor_id', $doctorId)
                            ->whereDate('appointment_date', Carbon::today())
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->count();
                        return $orderItemsCount + $appointmentsCount;
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->url(fn (Patient $record): string => PatientResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد مرضى لليوم')
            ->emptyStateDescription('لا توجد جلسات أو مواعيد مجدولة لليوم');
    }

    protected function getPatientsQuery(): Builder
    {
        $doctorId = auth()->id();
        
        return Patient::query()
            ->where(function (Builder $query) use ($doctorId) {
                // Patients with order items scheduled for today
                $query->whereHas('orders', function (Builder $q) use ($doctorId) {
                    $q->whereHas('orderItems', function (Builder $orderItemQuery) use ($doctorId) {
                        $orderItemQuery->where('doctor_id', $doctorId)
                            ->whereDate('next_session_date', Carbon::today())
                            ->where('status', '!=', 'completed');
                    });
                })
                // OR patients with appointments scheduled for today
                ->orWhereHas('appointments', function (Builder $q) use ($doctorId) {
                    $q->where('doctor_id', $doctorId)
                        ->whereDate('appointment_date', Carbon::today())
                        ->whereNotIn('status', ['completed', 'cancelled']);
                });
            })
            ->distinct();
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
            
            $this->dispatch('$refresh');
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
            
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('messages.error_ending_session'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

