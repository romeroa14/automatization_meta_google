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
        Schema::table('tenant_leads', function (Blueprint $table) {
            // Drop the global unique constraint
            $table->dropUnique('tenant_leads_phone_unique');
            
            // Add a composite unique constraint per workspace
            $table->unique(['workspace_id', 'phone_number'], 'leads_workspace_phone_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_leads', function (Blueprint $table) {
            $table->dropUnique('leads_workspace_phone_unique');
            $table->unique('phone_number', 'tenant_leads_phone_unique');
        });
    }
};
