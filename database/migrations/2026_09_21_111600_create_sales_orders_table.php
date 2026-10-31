<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('source')->default('pos');
            $table->string('customer_name')->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('fulfillment_status')->default('pending');
            $table->string('delivery_person')->nullable();
            $table->string('delivery_zone')->nullable();
            $table->string('delivery_address')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('cost_amount')->default(0);
            $table->timestamp('ordered_at');
            $table->timestamps();
            $table->index(['store_id', 'status', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
