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
            Schema::table('stores', function (Blueprint $table): void {
                $table->timestamp('free_plan_used_at')->nullable()->after('subscription_expired_notified_at');
            });

            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->boolean('is_renewable')->default(true)->after('is_active');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('free_plan_used_at');
            });

            Schema::table('subscription_plans', function (Blueprint $table): void {
                $table->dropColumn('is_renewable');
            });
        });
    }
};
