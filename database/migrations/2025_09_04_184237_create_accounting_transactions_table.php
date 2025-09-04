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
            $table->enum('transaction_type', ['income', 'expense', 'profit', 'refund'])->default('expense');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->string('reference_number')->nullable();
            $table->string('client_name')->nullable();
            $table->string('meta_campaign_id')->nullable();
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['transaction_type', 'status']);
            $table->index(['transaction_date']);
            $table->index(['due_date']);
            $table->index(['meta_campaign_id']);
            $table->index(['client_name']);
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