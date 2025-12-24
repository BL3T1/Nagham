<?php

namespace App\Filament\Doctor\Resources;

use App\Filament\Doctor\Resources\OrderItemResource\Pages;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderItemResource extends Resource
{
    protected static ?string $model = OrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'جلساتي';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('doctor_id', auth()->id())
            ->with(['order.patient', 'doctor']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order.patient.name')
                    ->label('المريض')
                    ->disabled(),
                Forms\Components\TextInput::make('price')
                    ->label('السعر')
                    ->numeric()
                    ->prefix('SYP')
                    ->minValue(0)
                    ->step(0.01),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Toggle::make('eligible_for_installment')
                    ->label('مؤهل للتقسيط'),
                Forms\Components\TextInput::make('down_payment')
                    ->label('المقدم')
                    ->numeric()
                    ->prefix('SYP')
                    ->minValue(0)
                    ->step(0.01)
                    ->visible(fn (Forms\Get $get) => $get('eligible_for_installment')),
                Forms\Components\DatePicker::make('next_session_date')
                    ->label('تاريخ الجلسة القادمة'),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.patient.name')
                    ->label('المريض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('رقم الطلب')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 2) . ' SYP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('eligible_for_installment')
                    ->boolean()
                    ->label('تقسيط'),
                Tables\Columns\TextColumn::make('next_session_date')
                    ->label('تاريخ الجلسة القادمة')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد الانتظار',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('تحديد كمكتمل')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (OrderItem $record) {
                        $record->update(['status' => 'completed']);
                    })
                    ->visible(fn (OrderItem $record) => $record->status !== 'completed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListOrderItems::route('/'),
            'edit' => Pages\EditOrderItem::route('/{record}/edit'),
        ];
    }
}

