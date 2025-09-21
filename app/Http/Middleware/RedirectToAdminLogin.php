<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectToAdminLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario no está autenticado y está intentando acceder a /admin
        if (!Auth::check() && $request->is('admin') && !$request->is('admin/login')) {
            return redirect('/admin/login');
        }
        
        // Si el usuario no está autenticado y está intentando acceder a cualquier ruta de admin
        if (!Auth::check() && $request->is('admin/*') && !$request->is('admin/login')) {
            return redirect('/admin/login');
        }
        
        return $next($request);
    }
}