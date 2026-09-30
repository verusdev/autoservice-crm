<?php

namespace App\Models;

use Database\Factories\OrderPartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPart extends Model
{
    /** @use HasFactory<OrderPartFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'part_id', 'name', 'sku', 'qty', 'price', 'cost'];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function getSumAttribute(): float
    {
        return round((float) $this->qty * (float) $this->price, 2);
    }

    public function getProfitAttribute(): float
    {
        return round((float) $this->sum - (float) $this->qty * (float) $this->cost, 2);
    }
}
