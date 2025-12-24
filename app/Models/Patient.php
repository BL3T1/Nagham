<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'address',
        'medical_history',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    public function getTotalDueAmountAttribute(): float
    {
        return (float) $this->orders()
            ->where('payment_status', '!=', 'paid')
            ->sum('remaining_amount') ?? 0.00;
    }

    public function getTotalPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount') ?? 0.00;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function hasUnpaidOrders(): bool
    {
        return $this->orders()->where('payment_status', '!=', 'paid')->exists();
    }
}

