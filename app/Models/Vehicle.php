<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $table = 'vehicles';

    protected $fillable = [
        'customer_id', 'brand', 'model', 'year', 'vin', 'plate', 'color', 'engine',
        'transmission', 'fuel', 'mileage', 'note', 'next_service_date', 'next_service_mileage',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'integer',
            'next_service_mileage' => 'integer',
            'engine' => 'decimal:1',
            'next_service_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getTitleAttribute(): string
    {
        $title = trim($this->brand.' '.$this->model);

        return $this->year ? $title.' '.$this->year : $title;
    }

    /** Сколько километров осталось до следующего ТО (null — если ТО не запланировано). */
    public function getServiceDueAttribute(): ?int
    {
        if (! $this->next_service_mileage) {
            return null;
        }

        return max(0, $this->next_service_mileage - $this->mileage);
    }
}
