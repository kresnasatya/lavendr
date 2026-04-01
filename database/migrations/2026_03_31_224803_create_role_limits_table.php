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
        Schema::create('role_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
            $table->unsignedInteger('daily_juice_limit')->default(0);
            $table->unsignedInteger('daily_meal_limit')->default(0);
            $table->unsignedInteger('daily_snack_limit')->default(0);
            $table->unsignedInteger('daily_point_limit')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_limits');
    }
};
