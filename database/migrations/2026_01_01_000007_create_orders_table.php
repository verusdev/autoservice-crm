<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();                 // номер заказ-наряда
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('new');
            // new → in_progress → waiting → ready → issued (+ cancelled)
            $table->unsignedInteger('mileage')->default(0);         // пробег при приёмке
            $table->dateTime('intake_at');                          // дата приёмки
            $table->dateTime('promised_at')->nullable();            // обещанная дата выдачи
            $table->dateTime('closed_at')->nullable();              // фактическая дата выдачи
            $table->text('complaint')->nullable();                  // жалоба клиента
            $table->text('note')->nullable();
            $table->decimal('discount', 5, 2)->default(0);         // скидка на наряд, %
            $table->decimal('total_work', 12, 2)->default(0);      // итого работы, ₽
            $table->decimal('total_parts', 12, 2)->default(0);     // итого запчасти, ₽
            $table->decimal('total', 12, 2)->default(0);           // итого к оплате, ₽
            $table->decimal('paid', 12, 2)->default(0);            // оплачено, ₽
            $table->boolean('is_invoice')->default(false);          // юр. лицо / НДС
            $table->timestamps();

            $table->index('status');
            $table->index('intake_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
