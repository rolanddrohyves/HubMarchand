<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('zones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('base_fee')->default(0);
            $table->unsignedBigInteger('free_above')->nullable();
            $table->unsignedBigInteger('price_per_kg')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'name']);
        });
        Schema::create('delivery_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('settled_at');
            $table->string('note')->nullable();
            $table->timestamps();
        });
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('courier_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('delivery_fee')->default(0)->after('total_amount');
            $table->decimal('weight_kg', 8, 2)->default(0)->after('delivery_fee');
            $table->string('delivery_note')->nullable()->after('delivery_address');
            $table->unsignedBigInteger('cod_collected')->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['courier_id']);
            $table->dropColumn(['courier_id', 'delivery_fee', 'weight_kg', 'delivery_note', 'cod_collected']);
        });
        Schema::dropIfExists('delivery_settlements');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('couriers');
    }
};
