<?php

namespace App\Filament\Admin\Widgets;

use App\Models\OrderItem;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class TopDoctorsWidget extends ChartWidget
{
    protected static ?string $heading = 'أفضل الأطباء حسب الجلسات المكتملة';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $doctors = User::where('role', 'doctor')
            ->withCount(['orderItems' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->orderBy('order_items_count', 'desc')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'الجلسات المكتملة',
                    'data' => $doctors->pluck('order_items_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                    ],
                ],
            ],
            'labels' => $doctors->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

