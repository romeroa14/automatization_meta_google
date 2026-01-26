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
        Schema::create('user_facebook_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Facebook User Info
            $table->string('facebook_user_id')->unique();
            $table->string('facebook_name')->nullable();
            $table->string('facebook_email')->nullable();
            
            // WhatsApp Business Account Info
            $table->string('waba_id')->nullable()->comment('WhatsApp Business Account ID');
            $table->string('business_id')->nullable()->comment('Facebook Business Portfolio ID');
            $table->json('waba_data')->nullable()->comment('WhatsApp Business Account full data');
            $table->string('signup_method')->nullable()->comment('embedded_signup, manual, etc.');
            
            // Token Info (encrypted in model)
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable(); // Permisos otorgados
            
            // Connected Assets
            $table->json('ad_accounts')->nullable(); // Lista de Ad Accounts accesibles
            $table->json('pages')->nullable(); // Lista de Pages accesibles
            $table->string('selected_ad_account_id')->nullable();
            $table->string('selected_page_id')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index('waba_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_facebook_connections');
    }
};
