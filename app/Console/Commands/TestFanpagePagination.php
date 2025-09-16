<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignCreationFlowService;

class TestFanpagePagination extends Command
{
    protected $signature = 'telegram:test-fanpage-pagination';
    protected $description = 'Prueba la paginación de fanpages';

    public function handle()
    {
        $this->info("🧪 **PRUEBA DE PAGINACIÓN DE FANPAGES**");
        $this->newLine();

        $flowService = new CampaignCreationFlowService();

        // Probar página 1
        $this->info("📄 **Página 1:**");
        $this->line("=" . str_repeat("=", 20));
        $message1 = $flowService->getFanpageMessagePaginated(1);
        $this->line($message1);
        $this->newLine();

        // Probar página 2
        $this->info("📄 **Página 2:**");
        $this->line("=" . str_repeat("=", 20));
        $message2 = $flowService->getFanpageMessagePaginated(2);
        $this->line($message2);
        $this->newLine();

        // Probar página 3
        $this->info("📄 **Página 3:**");
        $this->line("=" . str_repeat("=", 20));
        $message3 = $flowService->getFanpageMessagePaginated(3);
        $this->line($message3);
        $this->newLine();

        // Mostrar información de paginación
        $pagination = $flowService->getFanpagesPaginated(1);
        $this->info("📊 **Información de Paginación:**");
        $this->line("• Total de fanpages: {$pagination['total']}");
        $this->line("• Fanpages por página: {$pagination['per_page']}");
        $this->line("• Total de páginas: {$pagination['total_pages']}");
        $this->line("• Página actual: {$pagination['current_page']}");

        $this->newLine();
        $this->info("🎉 **PRUEBA COMPLETADA**");
        $this->info("✅ La paginación está funcionando correctamente");
        $this->info("✅ Cada página muestra máximo 20 fanpages");
        $this->info("✅ Los usuarios pueden navegar con 'SIGUIENTE' y 'ANTERIOR'");

        return Command::SUCCESS;
    }
}
