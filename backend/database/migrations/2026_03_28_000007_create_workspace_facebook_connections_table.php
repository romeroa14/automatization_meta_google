<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('workspace_facebook_connections');
        
        Schema::create('workspace_facebook_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            
            // Facebook User Info
            $table->string('facebook_user_id')->unique();
            $table->string('facebook_name')->nullable();
            $table->string('facebook_email')->nullable();
            
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
            
            $table->index(['workspace_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_facebook_connections');
    }
};
