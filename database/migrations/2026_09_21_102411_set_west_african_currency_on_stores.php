<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('currency', 3)->default('XOF')->change();
            });

            DB::table('stores')->where('currency', 'EUR')->update([
                'currency' => 'XOF',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            Schema::table('stores', function (Blueprint $table) {
                $table->string('currency', 3)->default('EUR')->change();
            });
        });
    }
};
