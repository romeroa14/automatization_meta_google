<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, eliminar duplicados existentes (mantener el más reciente)
        DB::statement('
            DELETE FROM conversations c1
            WHERE EXISTS (
                SELECT 1 FROM conversations c2
                WHERE c2.message_id = c1.message_id
                AND c2.message_id IS NOT NULL
                AND c2.id > c1.id
            )
        ');

        // Agregar índice único en message_id (solo para valores no nulos)
        // PostgreSQL no permite UNIQUE en columnas nullable directamente, así que usamos un índice único parcial
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS conversations_message_id_unique 
            ON conversations (message_id) 
            WHERE message_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS conversations_message_id_unique');
    }
};

