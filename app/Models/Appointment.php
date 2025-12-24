<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'order_item_id',
        'appointment_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeTomorrow($query)
    {
        return $query->whereDate('appointment_date', now()->addDay());
    }

    public function isPast(): bool
    {
        return $this->appointment_date && $this->appointment_date->isPast();
    }

    public function isToday(): bool
    {
        return $this->appointment_date && $this->appointment_date->isToday();
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}

