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
        Schema::create('report_facebook_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->onDelete('cascade');
            $table->foreignId('facebook_account_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable(); // Configuraciones específicas para esta cuenta en el reporte
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->unique(['report_id', 'facebook_account_id']);
            $table->index(['report_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_facebook_accounts');
    }
};
