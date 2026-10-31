<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('shop_theme')->default('minimal')->after('enabled_modules');
            $table->string('primary_color', 20)->default('#0891b2')->after('shop_theme');
            $table->string('favicon_path')->nullable()->after('logo_path');
            $table->string('subdomain')->nullable()->unique()->after('website');
            $table->string('custom_domain')->nullable()->unique()->after('subdomain');
            $table->json('homepage_blocks')->nullable()->after('enabled_modules');
            $table->json('web_payment_methods')->nullable()->after('payment_methods');
            $table->boolean('cash_on_delivery')->default(true)->after('web_payment_methods');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('is_published')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('is_published');
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropUnique(['subdomain']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'shop_theme',
                'primary_color',
                'favicon_path',
                'subdomain',
                'custom_domain',
                'homepage_blocks',
                'web_payment_methods',
                'cash_on_delivery',
            ]);
        });
    }
};
