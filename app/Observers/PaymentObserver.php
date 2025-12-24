<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $payment->order->updateAmounts();
    }

    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('amount')) {
            $payment->order->updateAmounts();
        }
    }

    public function deleted(Payment $payment): void
    {
        $payment->order->updateAmounts();
    }
}

