<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class PsqlProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psql:prod {--query= : Ejecutar una consulta específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Conecta directamente a la base de datos de producción PostgreSQL';

    /**
     * Configuración de la base de datos de producción
     */
    private array $dbConfig = [
        'host' => 'ep-still-resonance-a5g4cs1b.aws-us-east-2.pg.laravel.cloud',
        'port' => '5432',
        'database' => 'admetricas_db',
        'username' => 'laravel',
        'password' => 'npg_CPagJ5Z4SBjT',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Conectando a la base de datos de producción PostgreSQL...');
        
        // Verificar si psql está instalado
        if (!$this->isPsqlInstalled()) {
            $this->error('❌ psql no está instalado. Instálalo con: sudo apt-get install postgresql-client');
            return 1;
        }
        
        // Construir la cadena de conexión
        $connectionString = $this->buildConnectionString();
        
        // Si se proporciona una consulta específica
        if ($query = $this->option('query')) {
            return $this->executeQuery($connectionString, $query);
        }
        
        // Conectar interactivamente
        return $this->connectInteractive($connectionString);
    }
    
    /**
     * Verificar si psql está instalado
     */
    private function isPsqlInstalled(): bool
    {
        $result = Process::run('which psql');
        return $result->successful();
    }
    
    /**
     * Construir la cadena de conexión
     */
    private function buildConnectionString(): string
    {
        return sprintf(
            'postgresql://%s:%s@%s:%s/%s',
            $this->dbConfig['username'],
            $this->dbConfig['password'],
            $this->dbConfig['host'],
            $this->dbConfig['port'],
            $this->dbConfig['database']
        );
    }
    
    /**
     * Ejecutar una consulta específica
     */
    private function executeQuery(string $connectionString, string $query): int
    {
        $this->info("🔍 Ejecutando consulta: {$query}");
        
        $command = "psql '{$connectionString}' -c \"{$query}\"";
        
        $result = Process::run($command);
        
        if ($result->successful()) {
            $this->info('✅ Consulta ejecutada exitosamente:');
            $this->line($result->output());
            return 0;
        } else {
            $this->error('❌ Error al ejecutar la consulta:');
            $this->error($result->errorOutput());
            return 1;
        }
    }
    
    /**
     * Conectar de forma interactiva
     */
    private function connectInteractive(string $connectionString): int
    {
        $this->info('🔗 Conectando a la base de datos...');
        $this->info("📊 Host: {$this->dbConfig['host']}");
        $this->info("🗄️  Base de datos: {$this->dbConfig['database']}");
        $this->info("👤 Usuario: {$this->dbConfig['username']}");
        $this->newLine();
        
        $this->warn('⚠️  ADVERTENCIA: Estás conectándote a la base de datos de PRODUCCIÓN');
        $this->warn('⚠️  Ten cuidado con los comandos que ejecutes');
        $this->newLine();
        
        if (!$this->confirm('¿Estás seguro de que quieres continuar?')) {
            $this->info('❌ Conexión cancelada');
            return 0;
        }
        
        $this->info('🚀 Iniciando sesión interactiva de PostgreSQL...');
        $this->info('💡 Usa \\q para salir, \\? para ayuda');
        $this->newLine();
        
        // Ejecutar psql interactivamente
        $command = "psql '{$connectionString}'";
        
        $this->info("Ejecutando: {$command}");
        
        // Usar passthru para mantener la interactividad
        passthru($command, $exitCode);
        
        return $exitCode;
    }
}
