<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'phone', 'phone2', 'email', 'type', 'company',
        'source', 'address', 'birth_date', 'discount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'discount' => 'integer',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Сумма неоплаченных нарядов клиента. */
    public function debt(): float
    {
        return (float) $this->orders()->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(SUM(total),0) - COALESCE(SUM(paid),0) as due')->value('due');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->type === 'company' && $this->company ? $this->company : $this->name;
    }

    public static function types(): array
    {
        return ['person' => 'Физ. лицо', 'company' => 'Юр. лицо'];
    }
}
