<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('position')->nullable();          // слесарь, мастер-приёмщик, диагност...
            $table->string('role', 32)->default('mechanic'); // admin | advisor | mechanic
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'position', 'role', 'hourly_rate', 'post_id', 'is_active']);
        });
    }
};
