<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DiagnoseSystem extends Command
{
    protected $signature = 'diagnose:system';
    protected $description = 'Diagnosticar problemas del sistema';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO DEL SISTEMA');
        $this->line('');
        
        // 1. Verificar rutas de Filament
        $this->info('📋 1. VERIFICANDO RUTAS DE FILAMENT...');
        $allRoutes = Route::getRoutes();
        $adminRoutes = collect($allRoutes)->filter(function ($route) {
            return str_contains($route->uri(), 'admin');
        });
        
        if ($adminRoutes->count() > 0) {
            $this->info("✅ Se encontraron {$adminRoutes->count()} rutas de admin");
            foreach ($adminRoutes->take(5) as $route) {
                $this->line("   • {$route->methods()[0]} {$route->uri()}");
            }
        } else {
            $this->error('❌ No se encontraron rutas de admin');
        }
        
        $this->line('');
        
        // 2. Verificar configuración de autenticación
        $this->info('🔐 2. VERIFICANDO AUTENTICACIÓN...');
        $guard = config('auth.defaults.guard');
        $this->line("   • Guard por defecto: {$guard}");
        
        $userModel = config('auth.providers.users.model');
        $this->line("   • Modelo de usuario: {$userModel}");
        
        $userCount = User::count();
        $this->line("   • Usuarios en BD: {$userCount}");
        
        if ($userCount > 0) {
            $firstUser = User::first();
            $this->line("   • Primer usuario: {$firstUser->name} ({$firstUser->email})");
        }
        
        $this->line('');
        
        // 3. Verificar configuración de Filament
        $this->info('🎨 3. VERIFICANDO CONFIGURACIÓN DE FILAMENT...');
        
        if (class_exists('App\\Providers\\Filament\\AdminPanelProvider')) {
            $this->info('✅ AdminPanelProvider encontrado');
        } else {
            $this->error('❌ AdminPanelProvider no encontrado');
        }
        
        if (file_exists(app_path('Filament/Resources'))) {
            $this->info('✅ Directorio de Resources existe');
        } else {
            $this->warn('⚠️ Directorio de Resources no existe');
        }
        
        if (file_exists(app_path('Filament/Pages'))) {
            $this->info('✅ Directorio de Pages existe');
        } else {
            $this->warn('⚠️ Directorio de Pages no existe');
        }
        
        $this->line('');
        
        // 4. Verificar middleware
        $this->info('🛡️ 4. VERIFICANDO MIDDLEWARE...');
        $middleware = config('filament.middleware', []);
        if (!empty($middleware)) {
            $this->info('✅ Middleware configurado');
        } else {
            $this->warn('⚠️ Middleware no configurado');
        }
        
        $this->line('');
        
        // 5. Verificar archivos de configuración
        $this->info('⚙️ 5. VERIFICANDO ARCHIVOS DE CONFIGURACIÓN...');
        
        $configFiles = [
            'config/filament.php',
            'config/auth.php',
            'config/session.php',
        ];
        
        foreach ($configFiles as $file) {
            if (file_exists(base_path($file))) {
                $this->info("✅ {$file} existe");
            } else {
                $this->warn("⚠️ {$file} no existe");
            }
        }
        
        $this->line('');
        
        // 6. Recomendaciones
        $this->info('💡 RECOMENDACIONES:');
        $this->line('1. Verificar que el servidor esté ejecutándose en el puerto correcto');
        $this->line('2. Limpiar caché: php artisan config:clear && php artisan route:clear');
        $this->line('3. Verificar que no haya conflictos de middleware');
        $this->line('4. Revisar logs de Laravel para errores específicos');
        
        $this->line('');
        $this->info('🎉 Diagnóstico completado!');
    }
}