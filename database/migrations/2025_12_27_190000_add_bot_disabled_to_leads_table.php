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
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('bot_disabled')->default(false)->after('confidence_score')->comment('Si es true, el bot no responderá (intervención humana activa)');
            $table->timestamp('last_human_intervention_at')->nullable()->after('bot_disabled')->comment('Última vez que un agente humano escribió. Después de 20 min, el bot puede responder de nuevo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['bot_disabled', 'last_human_intervention_at']);
        });
    }
};

