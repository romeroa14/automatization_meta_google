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
        Schema::create('facebook_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_account_id')->constrained()->onDelete('cascade');
            $table->string('campaign_id')->index();
            $table->string('campaign_name');
            $table->string('campaign_status')->default('ACTIVE');
            
            // Campo JSON para todas las estadísticas (más flexible)
            $table->json('statistics')->nullable();
            
            // Fechas del período de datos
            $table->date('date_start')->nullable();
            $table->date('date_stop')->nullable();
            $table->string('date_range')->nullable(); // last_7d, last_30d, etc.
            
            // Control
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            // Índices
            $table->unique(['facebook_account_id', 'campaign_id', 'date_range']);
            $table->index(['campaign_status', 'last_updated']);
            $table->index(['date_start', 'date_stop']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_campaigns');
    }
};
