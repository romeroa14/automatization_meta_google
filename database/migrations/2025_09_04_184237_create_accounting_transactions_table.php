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
        Schema::create('accounting_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_reconciliation_id')->nullable()->constrained('campaign_plan_reconciliations')->onDelete('set null');
            $table->foreignId('advertising_plan_id')->nullable()->constrained('advertising_plans')->onDelete('set null');
            $table->string('description');
            $table->decimal('income', 10, 2)->default(0); // Campo para ingreso
            $table->decimal('expense', 10, 2)->default(0); // Campo para gasto
            $table->decimal('profit', 10, 2)->default(0); // Campo para ganancia
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->string('reference_number')->nullable();
            $table->string('client_name')->nullable();
            $table->string('meta_campaign_id')->nullable();
            $table->date('campaign_start_date')->nullable();
            $table->date('campaign_end_date')->nullable();
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['status']);
            $table->index(['transaction_date']);
            $table->index(['due_date']);
            $table->index(['meta_campaign_id']);
            $table->index(['client_name']);
            $table->index(['campaign_start_date']);
            $table->index(['campaign_end_date']);
            $table->unique(['campaign_reconciliation_id', 'meta_campaign_id'], 'unique_campaign_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_transactions');
    }
};