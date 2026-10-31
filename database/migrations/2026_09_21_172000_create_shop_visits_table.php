<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_key', 64);
            $table->timestamp('visited_at');
            $table->timestamps();
            $table->index(['store_id', 'visited_at']);
            $table->index(['store_id', 'visitor_key', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_visits');
    }
};
