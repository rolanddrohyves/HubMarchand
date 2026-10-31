<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->string('payment_method')->default('cash')->after('status');
            $table->unsignedBigInteger('paid_amount')->default(0)->after('cost_amount');
            $table->date('due_at')->nullable()->after('paid_amount');
            $table->index(['store_id', 'payment_method', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['store_id', 'payment_method', 'due_at']);
            $table->dropColumn(['customer_id', 'payment_method', 'paid_amount', 'due_at']);
        });
    }
};
