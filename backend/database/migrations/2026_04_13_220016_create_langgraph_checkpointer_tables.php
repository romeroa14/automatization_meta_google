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
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->string('thread_id');
            $table->string('checkpoint_ns')->default('');
            $table->string('checkpoint_id');
            $table->string('parent_checkpoint_id')->nullable();
            $table->string('type')->nullable();
            $table->binary('checkpoint');
            $table->binary('metadata');
            
            $table->primary(['thread_id', 'checkpoint_ns', 'checkpoint_id']);
        });

        Schema::create('checkpoint_blobs', function (Blueprint $table) {
            $table->string('thread_id');
            $table->string('checkpoint_ns')->default('');
            $table->string('channel');
            $table->string('version');
            $table->string('type');
            $table->binary('blob')->nullable();
            
            $table->primary(['thread_id', 'checkpoint_ns', 'channel', 'version']);
        });

        Schema::create('checkpoint_writes', function (Blueprint $table) {
            $table->string('thread_id');
            $table->string('checkpoint_ns')->default('');
            $table->string('checkpoint_id');
            $table->string('task_id');
            $table->integer('idx');
            $table->string('channel');
            $table->string('type')->nullable();
            $table->binary('blob');
            
            $table->primary(['thread_id', 'checkpoint_ns', 'checkpoint_id', 'task_id', 'idx']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkpoint_writes');
        Schema::dropIfExists('checkpoint_blobs');
        Schema::dropIfExists('checkpoints');
    }
};
