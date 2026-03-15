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
        // Primero, eliminar duplicados existentes (mantener el más reciente por phone_number)
        DB::statement('
            DELETE FROM leads c1
            WHERE EXISTS (
                SELECT 1 FROM leads c2
                WHERE c2.phone_number = c1.phone_number
                AND c2.phone_number IS NOT NULL
                AND c2.id > c1.id
            )
        ');

        // Eliminar índice si existe (por si acaso hay uno mal configurado)
        DB::statement('DROP INDEX IF EXISTS leads_phone_number_unique');

        // Agregar índice único en phone_number
        // IMPORTANTE: Para que funcione con "ON CONFLICT" en n8n, necesitamos un índice único estándar
        // Si phone_number puede ser NULL, usamos un índice único parcial
        DB::statement('
            CREATE UNIQUE INDEX leads_phone_number_unique 
            ON leads (phone_number) 
            WHERE phone_number IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS leads_phone_number_unique');
    }
};

