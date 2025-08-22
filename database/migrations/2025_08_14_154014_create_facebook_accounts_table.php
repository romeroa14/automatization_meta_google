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
        Schema::create('facebook_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('account_id');
            $table->string('app_id');
            $table->string('app_secret');
            $table->text('access_token');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->string('selected_ad_account_id')->nullable();
            $table->string('selected_page_id')->nullable();
            $table->json('selected_campaign_ids')->nullable();
            $table->json('selected_ad_ids')->nullable();
            $table->timestamps();
            
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facebook_accounts');
    }
};
