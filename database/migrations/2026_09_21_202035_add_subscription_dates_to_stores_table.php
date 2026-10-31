<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_plan_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_started_at')->index();
            $table->timestamp('subscription_expired_notified_at')->nullable()->after('subscription_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropIndex(['subscription_ends_at']);
            $table->dropColumn([
                'subscription_started_at',
                'subscription_ends_at',
                'subscription_expired_notified_at',
            ]);
        });
    }
};
