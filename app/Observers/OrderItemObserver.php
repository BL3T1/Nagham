<?php

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
    public function created(OrderItem $orderItem): void
    {
        $orderItem->order->updateAmounts();
    }

    public function updated(OrderItem $orderItem): void
    {
        if ($orderItem->isDirty(['price', 'status'])) {
            $orderItem->order->updateAmounts();
        }
    }

    public function deleted(OrderItem $orderItem): void
    {
        $orderItem->order->updateAmounts();
    }
}

