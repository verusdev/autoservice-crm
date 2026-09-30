<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vin', 32)->nullable()->index();
            $table->string('plate', 16)->nullable();          // госномер
            $table->string('color')->nullable();
            $table->decimal('engine', 5, 1)->nullable();      // объём двигателя, л
            $table->string('transmission', 32)->nullable();
            $table->string('fuel', 32)->nullable();
            $table->unsignedInteger('mileage')->default(0);   // текущий пробег, км
            $table->string('note')->nullable();
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('next_service_mileage')->nullable();
            $table->timestamps();

            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
