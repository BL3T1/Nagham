<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = Order::sum('total_amount');
        $paidRevenue = Order::sum('paid_amount');
        $pendingRevenue = Order::sum('remaining_amount');

        return [
            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 2) . ' SYP')
                ->description('جميع الطلبات')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('الإيرادات المدفوعة', number_format($paidRevenue, 2) . ' SYP')
                ->description('المدفوعات المستلمة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('الإيرادات المعلقة', number_format($pendingRevenue, 2) . ' SYP')
                ->description('الرصيد المستحق')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}

