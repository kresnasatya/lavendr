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
        Schema::table('users', function (Blueprint $table) {
            $table->string('card_number')->unique()->nullable()->after('email');
            $table->integer('daily_quota')->default(100)->after('card_number');
            $table->boolean('is_active')->default(true)->after('daily_quota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'daily_quota', 'is_active']);
        });
    }
};
