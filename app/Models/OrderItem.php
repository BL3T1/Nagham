<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'doctor_id',
        'status',
        'price',
        'eligible_for_installment',
        'down_payment',
        'notes',
        'next_session_date',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'eligible_for_installment' => 'boolean',
            'next_session_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canInstallment(): bool
    {
        return $this->eligible_for_installment === true && $this->price > 0;
    }

    public function getRemainingAmountAttribute(): float
    {
        $paidAmount = (float) $this->payments()
            ->where('payment_type', '!=', 'refund')
            ->sum('amount') ?? 0.00;
        
        $remaining = round(($this->price ?? 0.00) - $paidAmount, 2);
        
        return max(0, $remaining);
    }
}

