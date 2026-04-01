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
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->enum('category', ['juice', 'meal', 'snack'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_slots', function (Blueprint $table) {
            $table->string('category')->change();
        });
    }
};
