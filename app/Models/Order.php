<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'number', 'customer_id', 'vehicle_id', 'post_id', 'mechanic_id', 'advisor_id',
        'status', 'mileage', 'intake_at', 'promised_at', 'closed_at', 'complaint', 'note',
        'discount', 'total_work', 'total_parts', 'total', 'paid', 'is_invoice',
    ];

    protected function casts(): array
    {
        return [
            'intake_at' => 'datetime',
            'promised_at' => 'datetime',
            'closed_at' => 'datetime',
            'discount' => 'decimal:2',
            'total_work' => 'decimal:2',
            'total_parts' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'is_invoice' => 'boolean',
        ];
    }

    /* ---------------- relations ---------------- */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(OrderPart::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /* ---------------- statuses ---------------- */

    public static function statuses(): array
    {
        return [
            'new' => ['label' => 'Новый', 'color' => 'blue', 'step' => 1],
            'in_progress' => ['label' => 'В работе', 'color' => 'amber', 'step' => 2],
            'waiting' => ['label' => 'Ожидает запчасть', 'color' => 'violet', 'step' => 3],
            'ready' => ['label' => 'Готов к выдаче', 'color' => 'green', 'step' => 4],
            'issued' => ['label' => 'Выдан', 'color' => 'gray', 'step' => 5],
            'cancelled' => ['label' => 'Отменён', 'color' => 'red', 'step' => 0],
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statuses()[$status]['label'] ?? $status;
    }

    public static function statusColor(?string $status): string
    {
        return self::statuses()[$status]['color'] ?? 'gray';
    }

    /** Активные (незакрытые) статусы — для отчёта WIP. */
    public static function openStatuses(): array
    {
        return ['new', 'in_progress', 'waiting', 'ready'];
    }

    /* ---------------- calculations ---------------- */

    public function getBalanceAttribute(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }

    public function isPaid(): bool
    {
        return $this->balance <= 0.009;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::openStatuses(), true);
    }

    public function totalHours(): float
    {
        return round((float) $this->works->sum('norm_hours'), 2);
    }

    public function doneHours(): float
    {
        return round((float) $this->works->where('status', 'done')->sum('norm_hours'), 2);
    }

    public function progress(): int
    {
        $all = $this->works->count();
        if ($all === 0) {
            return $this->status === 'issued' ? 100 : 0;
        }

        return (int) round($this->works->where('status', 'done')->count() / $all * 100);
    }

    /** Пересчитать итоги наряда по позициям работ и запчастей. */
    public function recalculate(): void
    {
        $work = (float) $this->works()->sum('price');
        $parts = (float) $this->parts()->selectRaw('COALESCE(SUM(qty * price),0) as s')->value('s');
        $subtotal = $work + $parts;

        $customerDiscount = (float) ($this->customer->discount ?? 0);
        $discount = max((float) $this->discount, $customerDiscount);
        $total = round($subtotal * (1 - $discount / 100), 2);

        $this->forceFill([
            'total_work' => round($work, 2),
            'total_parts' => round($parts, 2),
            'total' => $total,
        ])->saveQuietly();
    }

    /** Пересчитать оплаченную сумму по платежам. */
    public function recalculatePaid(): void
    {
        $this->forceFill(['paid' => round((float) $this->payments()->sum('amount'), 2)])->saveQuietly();
    }

    /** Сгенерировать следующий номер заказ-наряда вида ЗН-2026-0001. */
    public static function nextNumber(): string
    {
        $year = now()->year;
        $last = static::where('number', 'like', "ЗН-{$year}-%")
            ->orderByDesc('id')->value('number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('ЗН-%d-%04d', $year, $seq);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $like = '%'.$term.'%';
            $q->where('number', 'like', $like)
                ->orWhereHas('customer', fn ($c) => $q->orWhere('name', 'like', $like))
                ->orWhereHas('vehicle', fn ($v) => $q->orWhere('plate', 'like', $like)
                    ->orWhere('vin', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('model', 'like', $like));
        });
    }
}
