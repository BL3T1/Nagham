<?php

namespace App\Providers;

use App\Models\OrderItem;
use App\Models\Payment;
use App\Observers\OrderItemObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        OrderItem::observe(OrderItemObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
