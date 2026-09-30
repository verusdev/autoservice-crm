<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone', 'position', 'role', 'hourly_rate', 'post_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hourly_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** Наряды, закреплённые за сотрудником как за мастером. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'mechanic_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'mechanic_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Инициалы для аватара. */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);

        return mb_strtoupper($first.$second);
    }

    public static function roles(): array
    {
        return [
            'admin' => 'Управляющий',
            'advisor' => 'Мастер-приёмщик',
            'mechanic' => 'Автослесарь',
        ];
    }

    public static function roleLabel(?string $role): string
    {
        return self::roles()[$role] ?? (string) $role;
    }
}
