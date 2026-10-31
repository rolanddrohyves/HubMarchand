<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('shop_tagline', 255)->nullable()->after('shop_theme');
            $table->text('shop_announcement')->nullable()->after('shop_tagline');
            $table->string('homepage_banner_path')->nullable()->after('homepage_blocks');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('web_collection', 100)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('web_collection');
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn(['shop_tagline', 'shop_announcement', 'homepage_banner_path']);
        });
    }
};
