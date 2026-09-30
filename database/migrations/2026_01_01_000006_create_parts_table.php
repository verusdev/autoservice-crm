<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->nullable()->index();   // артикул
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('category', 64)->default('Запчасти');
            $table->string('unit', 12)->default('шт');         // шт / л / кг / м
            $table->decimal('price', 12, 2)->default(0);        // закупочная
            $table->decimal('sell_price', 12, 2)->default(0);   // розничная (отдаём клиенту)
            $table->unsignedInteger('min_stock')->default(2);  // точка заказа
            $table->decimal('qty', 10, 2)->default(0);         // текущий остаток
            $table->string('location', 32)->nullable();        // ячейка склада
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
