<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanAdImages extends Command
{
    protected $signature = 'ads:clean-images {--days=30 : Eliminar imágenes más antiguas que X días}';
    
    protected $description = 'Limpia imágenes de anuncios antiguas del storage local';

    public function handle()
    {
        $days = (int) $this->option('days');
        $directory = storage_path('app/public/ad-images');
        
        if (!is_dir($directory)) {
            $this->info('No existe el directorio de imágenes de anuncios.');
            return 0;
        }

        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deletedCount = 0;
        $totalSize = 0;

        $files = glob($directory . '/*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                $size = filesize($file);
                $totalSize += $size;
                
                if (unlink($file)) {
                    $deletedCount++;
                    $this->line("Eliminado: " . basename($file));
                }
            }
        }

        $this->info("Limpieza completada:");
        $this->info("- Archivos eliminados: {$deletedCount}");
        $this->info("- Espacio liberado: " . $this->formatBytes($totalSize));
        
        return 0;
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}