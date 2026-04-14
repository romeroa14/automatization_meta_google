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
        Schema::dropIfExists('campaign_metrics');
        
        Schema::create('campaign_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('meta_campaign_id');
            $table->string('campaign_name');
            $table->decimal('spend', 10, 2)->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('leads_generated')->default(0);
            $table->date('date');
            $table->timestamps();

            // Unique compuesto por día
            $table->unique(['workspace_id', 'meta_campaign_id', 'date'], 'metrics_unique_by_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_metrics');
    }
};
