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
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'platform')) {
                $table->string('platform')->default('whatsapp');
            } else {
                $table->string('platform')->default('whatsapp')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'platform')) {
                // If we changed it, we might want to revert the default, but dropping is safer if we added it.
                // Since we don't know if it was added or changed, we'll just leave it or revert the default.
                $table->string('platform')->nullable()->change();
            }
        });
    }
};
