<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class TestRedirects extends Command
{
    protected $signature = 'test:redirects';
    protected $description = 'Probar redirecciones del sistema';

    public function handle()
    {
        $this->info('🧪 PROBANDO REDIRECCIONES DEL SISTEMA');
        $this->line('');
        
        // 1. Verificar rutas principales
        $this->info('📋 1. VERIFICANDO RUTAS PRINCIPALES...');
        
        $routes = [
            '/' => 'Página principal',
            '/admin' => 'Panel de administración',
            '/admin/login' => 'Login de admin',
        ];
        
        foreach ($routes as $path => $description) {
            $route = Route::getRoutes()->match(request()->create($path, 'GET'));
            if ($route) {
                $this->info("✅ {$path} - {$description}");
                $this->line("   • Controlador: {$route->getActionName()}");
            } else {
                $this->error("❌ {$path} - {$description} (No encontrada)");
            }
        }
        
        $this->line('');
        
        // 2. Verificar configuración de URL
        $this->info('🌐 2. VERIFICANDO CONFIGURACIÓN DE URL...');
        $appUrl = config('app.url');
        $this->line("   • APP_URL: {$appUrl}");
        
        $currentUrl = URL::current();
        $this->line("   • URL actual: {$currentUrl}");
        
        $this->line('');
        
        // 3. Verificar middleware de autenticación
        $this->info('🔐 3. VERIFICANDO MIDDLEWARE DE AUTENTICACIÓN...');
        
        $adminRoute = Route::getRoutes()->match(request()->create('/admin', 'GET'));
        if ($adminRoute) {
            $middleware = $adminRoute->gatherMiddleware();
            $this->line("   • Middleware de /admin:");
            foreach ($middleware as $mw) {
                $this->line("     - {$mw}");
            }
        }
        
        $this->line('');
        
        // 4. Verificar configuración de sesión
        $this->info('🍪 4. VERIFICANDO CONFIGURACIÓN DE SESIÓN...');
        $sessionDriver = config('session.driver');
        $sessionLifetime = config('session.lifetime');
        $this->line("   • Driver de sesión: {$sessionDriver}");
        $this->line("   • Tiempo de vida: {$sessionLifetime} minutos");
        
        $this->line('');
        
        // 5. Recomendaciones específicas
        $this->info('💡 RECOMENDACIONES ESPECÍFICAS:');
        $this->line('1. Verificar que APP_URL esté configurado correctamente');
        $this->line('2. Asegurar que el servidor esté ejecutándose en el puerto correcto');
        $this->line('3. Verificar que no haya conflictos de middleware');
        $this->line('4. Revisar logs de Laravel para errores específicos');
        $this->line('5. Probar acceso directo a /admin/login');
        
        $this->line('');
        $this->info('🎉 Pruebas de redirección completadas!');
    }
}