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
        Schema::table('products', function (Blueprint $table) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('purchase_price')->default(0)->after('unit_price');
                $table->string('barcode', 100)->nullable()->after('sku');
                $table->string('image_path')->nullable()->after('barcode');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['purchase_price', 'barcode', 'image_path']);
            });
        });
    }
};
