<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'customer_id', 'user_id', 'amount', 'method', 'paid_at', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function methods(): array
    {
        return [
            'cash' => 'Наличные',
            'card' => 'Карта',
            'transfer' => 'Перевод',
            'credit' => 'В счёт долга',
        ];
    }

    public static function methodLabel(?string $method): string
    {
        return self::methods()[$method] ?? $method;
    }
}
