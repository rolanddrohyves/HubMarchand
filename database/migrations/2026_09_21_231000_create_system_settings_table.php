<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('HubMarchand');
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#0891b2');
            $table->string('secondary_color', 7)->default('#0f172a');
            $table->string('accent_color', 7)->default('#f59e0b');
            $table->string('support_email')->nullable();
            $table->string('support_phone', 30)->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
