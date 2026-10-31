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
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('status')->default('active')->after('name');
            $table->unsignedBigInteger('subscription_plan_id')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('subscription_plan_id');
            $table->index(['status', 'subscription_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropIndex(['status', 'subscription_plan_id']);
            $table->dropColumn(['subscription_plan_id', 'status', 'suspended_at']);
        });
    }
};
