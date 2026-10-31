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
        Schema::table('subscription_plans', function (Blueprint $table) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->unsignedInteger('annual_price')->nullable()->after('monthly_price');
                $table->json('payment_methods')->nullable()->after('ai_included');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->dropColumn(['annual_price', 'payment_methods']);
            });
        });
    }
};
