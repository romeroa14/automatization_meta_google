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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('user_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('client_name')->nullable();
            $table->string('intent')->nullable();
            $table->string('lead_level')->nullable();
            $table->string('stage')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->boolean('bot_disabled')->default(false)->comment('Si es true, el bot no responderá (intervención humana activa)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
