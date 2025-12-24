<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'patient_id',
        'received_by',
        'amount',
        'payment_type',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopeFull($query)
    {
        return $query->where('payment_type', 'full');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_type', 'partial');
    }

    public function scopeInstallment($query)
    {
        return $query->where('payment_type', 'installment');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . config('app.currency', 'USD');
    }
}

