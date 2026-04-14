#!/bin/bash

# Script para limpiar archivos duplicados después de la reestructuración
# Mantiene: backend/, frontend-web/, mobile-app/, .git/, .github/, README.md

echo "🧹 Limpiando archivos duplicados..."

# Directorios a eliminar (ya están en backend/)
DIRS_TO_REMOVE=(
    "app"
    "bootstrap"
    "config"
    "database"
    "public"
    "resources"
    "routes"
    "storage"
    "tests"
    "vendor"
    "node_modules"
    "scripts"
    "frontend"
)

# Archivos a eliminar (ya están en backend/)
FILES_TO_REMOVE=(
    "artisan"
    "composer.json"
    "composer.lock"
    "package.json"
    "package-lock.json"
    "phpunit.xml"
    "postcss.config.js"
    "tailwind.config.js"
    "vite.config.js"
    ".editorconfig"
    ".env"
    ".env.example"
    ".gitattributes"
    ".gitignore"
    "configure_instagram_env.sh"
    "docker-compose.frontend.yml"
    "test_waba.php"
    "SOLUCION_RAPIDA_N8N.md"
)

# Eliminar directorios
for dir in "${DIRS_TO_REMOVE[@]}"; do
    if [ -d "$dir" ]; then
        echo "  Eliminando directorio: $dir"
        rm -rf "$dir"
    fi
done

# Eliminar archivos
for file in "${FILES_TO_REMOVE[@]}"; do
    if [ -f "$file" ]; then
        echo "  Eliminando archivo: $file"
        rm -f "$file"
    fi
done

echo "✅ Limpieza completada!"
echo ""
echo "📁 Estructura actual:"
ls -la | grep -E "^d|^-" | awk '{print $9}' | grep -v "^\.$" | grep -v "^\.\.$"
