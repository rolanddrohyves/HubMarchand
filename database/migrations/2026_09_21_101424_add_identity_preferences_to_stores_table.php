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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
            $table->string('country', 2)->default('FR')->after('address');
            $table->string('currency', 3)->default('EUR')->after('country');
            $table->string('timezone')->default('Europe/Paris')->after('currency');
            $table->string('date_format', 20)->default('d/m/Y')->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'country',
                'currency',
                'timezone',
                'date_format',
            ]);
        });
    }
};
