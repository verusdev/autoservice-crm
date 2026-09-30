<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 32)->index();
            $table->string('phone2', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('type', 16)->default('person');   // person | company
            $table->string('company')->nullable();
            $table->string('source')->nullable();            // откуда пришёл: 2GIS, рекомендация, сайт...
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedTinyInteger('discount')->default(0); // персональная скидка, %
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
