<?php

namespace App\Models;

use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    protected $fillable = [
        'sku', 'name', 'brand', 'category', 'unit',
        'price', 'sell_price', 'min_stock', 'qty', 'location', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'min_stock' => 'integer',
            'qty' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderParts(): HasMany
    {
        return $this->hasMany(OrderPart::class);
    }

    /** Текущий остаток. Актуальное значение хранится в колонке parts.qty
     *  и поддерживается автоматически при movements прихода/расхода. */
    public function isLow(): bool
    {
        return (float) $this->qty <= (float) $this->min_stock;
    }

    public function getMarginAttribute(): float
    {
        return (float) $this->sell_price - (float) $this->price;
    }
}
