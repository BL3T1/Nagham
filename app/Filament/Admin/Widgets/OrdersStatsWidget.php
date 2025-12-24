<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayOrders = Order::whereDate('created_at', today())->count();
        $monthOrders = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $totalOrders = Order::count();

        return [
            Stat::make('طلبات اليوم', $todayOrders)
                ->description('الطلبات المنشأة اليوم')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
            Stat::make('هذا الشهر', $monthOrders)
                ->description('طلبات هذا الشهر')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
            Stat::make('إجمالي الطلبات', $totalOrders)
                ->description('كل الوقت')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}

