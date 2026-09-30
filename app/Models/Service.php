<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'category', 'norm_hours', 'price', 'is_active'];

    protected function casts(): array
    {
        return [
            'norm_hours' => 'decimal:2',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function categories(): array
    {
        return ['ТО', 'Ремонт', 'Диагностика', 'Кузовной ремонт', 'Компьютерная диагностика', 'Шиномонтаж'];
    }
}
