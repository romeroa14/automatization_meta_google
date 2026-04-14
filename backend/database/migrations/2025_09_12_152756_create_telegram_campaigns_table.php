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
        Schema::create('telegram_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_user_id')->index();
            $table->string('telegram_conversation_id')->nullable();
            $table->string('campaign_name');
            $table->string('objective'); // TRAFFIC, CONVERSIONS, REACH, etc.
            $table->string('budget_type'); // campaign_daily_budget, adset_daily_budget
            $table->decimal('daily_budget', 10, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('targeting_data')->nullable(); // Datos de targeting
            $table->json('ad_data')->nullable(); // Datos del anuncio
            $table->string('media_type')->nullable(); // image, video, carousel
            $table->string('media_url')->nullable(); // URL del archivo subido
            $table->text('ad_copy')->nullable(); // Texto del anuncio
            $table->string('meta_campaign_id')->nullable(); // ID de la campaña creada en Meta
            $table->string('meta_adset_id')->nullable(); // ID del adset creado en Meta
            $table->string('meta_ad_id')->nullable(); // ID del anuncio creado en Meta
            $table->string('status')->default('pending'); // pending, created, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_campaigns');
    }
};
