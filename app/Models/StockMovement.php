<?php

namespace App\Models;

use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'part_id', 'type', 'qty', 'price', 'order_id', 'user_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Количество со знаком: приход увеличивает остаток, расход уменьшает. */
    public function signedQty(): float
    {
        $qty = (float) $this->qty;

        return match ($this->type) {
            'in', 'adjust' => $qty,
            'out' => -$qty,
            default => 0,
        };
    }

    public static function types(): array
    {
        return [
            'in' => 'Приход',
            'out' => 'Расход',
            'adjust' => 'Инвентаризация',
        ];
    }
}
