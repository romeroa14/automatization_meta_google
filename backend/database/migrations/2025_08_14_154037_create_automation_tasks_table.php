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
        Schema::create('automation_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('facebook_account_id')->constrained('facebook_accounts')->onDelete('cascade');
            $table->foreignId('google_sheet_id')->constrained('google_sheets')->onDelete('cascade');
            $table->enum('frequency', ['hourly', 'daily', 'weekly', 'monthly', 'custom']);
            $table->time('scheduled_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['is_active']);
            $table->index(['next_run', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_tasks');
    }
};
