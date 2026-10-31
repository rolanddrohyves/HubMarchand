<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('tax_id')->nullable()->after('email');
            $table->string('payment_terms')->default('immediate')->after('segment');
            $table->unsignedTinyInteger('deposit_percent')->default(0)->after('payment_terms');
        });
        Schema::create('b2b_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('b2b_documents')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('type')->default('quote');
            $table->string('status')->default('draft');
            $table->date('issued_at');
            $table->date('valid_until')->nullable();
            $table->date('due_at')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'type', 'status', 'due_at']);
        });
        Schema::create('b2b_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_document_items');
        Schema::dropIfExists('b2b_documents');
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['tax_id', 'payment_terms', 'deposit_percent']);
        });
    }
};
