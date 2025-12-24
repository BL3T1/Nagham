<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'patient_id',
        'created_by',
        'status',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function updateAmounts(): void
    {
        $precision = config('medical_center.payment.precision', 2);
        $threshold = config('medical_center.payment.floating_point_threshold', 0.01);

        $this->total_amount = (float) $this->orderItems()->sum('price') ?? 0.00;
        $this->paid_amount = (float) $this->payments()
            ->where('payment_type', '!=', 'refund')
            ->sum('amount') ?? 0.00;
        
        $this->remaining_amount = round($this->total_amount - $this->paid_amount, $precision);
        
        if ($this->remaining_amount <= $threshold) {
            $this->payment_status = 'paid';
            $this->remaining_amount = 0.00;
        } elseif ($this->paid_amount > $threshold) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        
        $this->save();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', '!=', 'paid');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->remaining_amount <= 0.01;
    }

    public function hasPendingItems(): bool
    {
        return $this->orderItems()->where('status', 'pending')->exists();
    }
}

