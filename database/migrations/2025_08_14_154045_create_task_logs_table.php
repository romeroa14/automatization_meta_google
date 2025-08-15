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
        Schema::create('task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_task_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['running', 'success', 'failed']);
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('records_processed')->default(0);
            $table->float('execution_time')->default(0);
            $table->json('data_synced')->nullable();
            $table->timestamps();
            
            $table->index(['automation_task_id', 'started_at']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_logs');
    }
};
