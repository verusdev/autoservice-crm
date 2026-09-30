<?php

namespace App\Models;

use Database\Factories\WorkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Work extends Model
{
    /** @use HasFactory<WorkFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id', 'service_id', 'name', 'norm_hours',
        'hourly_rate', 'price', 'mechanic_id', 'status', 'completed_at', 'comment',
    ];

    protected function casts(): array
    {
        return [
            'norm_hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'price' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    /** Себестоимость работы для расчёта маржи (нормо-часы × ставку мастера). */
    public function cost(): float
    {
        return round((float) $this->norm_hours * (float) $this->hourly_rate, 2);
    }

    public function profit(): float
    {
        return round((float) $this->price - $this->cost(), 2);
    }
}
