<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                      // снимок названия на момент продажи
            $table->string('sku', 64)->nullable();
            $table->decimal('qty', 10, 2)->default(1);
            $table->decimal('price', 12, 2)->default(0);  // цена продажи за единицу
            $table->decimal('cost', 12, 2)->default(0);   // закупочная, для прибыли
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_parts');
    }
};
