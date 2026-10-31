<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->date('reminded_at');
            $table->timestamps();
            $table->index(['customer_id', 'reminded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_reminders');
    }
};
