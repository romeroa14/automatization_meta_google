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
        Schema::dropIfExists('legacy_conversations');
        Schema::dropIfExists('legacy_leads');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('leads');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreating these tables is complex and ideally we just drop them,
        // but to be safe we can leave down() empty or throw an exception.
    }
};
