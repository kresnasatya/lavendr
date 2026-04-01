<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recharge_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
            $table->enum('mode', ['daily', 'dual_period'])->default('daily');
            $table->time('recharge_time')->default('00:00:00');
            $table->time('breakfast_time')->nullable();
            $table->time('lunch_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recharge_settings');
    }
};
