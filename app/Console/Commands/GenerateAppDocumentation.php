<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAppDocumentation extends Command
{
    protected $signature = 'meta:generate-app-docs';
    protected $description = 'Genera documentación para hacer la app de Facebook pública';

    public function handle()
    {
        $this->info("📋 **DOCUMENTACIÓN PARA APP PÚBLICA DE FACEBOOK**");
        $this->newLine();

        $this->info("🎯 **¿Qué necesitas para hacer tu app pública?**");
        $this->line("=" . str_repeat("=", 45));
        $this->newLine();

        $this->info("✅ **Requisitos MÍNIMOS (sin empresa):**");
        $this->line("• Verificación de identidad personal (tu documento)");
        $this->line("• Política de privacidad (puedes usar plantilla)");
        $this->line("• Términos de servicio (puedes usar plantilla)");
        $this->line("• Descripción de la app");
        $this->newLine();

        $this->info("❌ **NO necesitas:**");
        $this->line("• Empresa registrada");
        $this->line("• Documentos comerciales");
        $this->line("• Licencias especiales");
        $this->line("• Verificación empresarial");
        $this->newLine();

        $this->info("📝 **PLANTILLAS DE DOCUMENTOS:**");
        $this->line("=" . str_repeat("=", 30));
        $this->newLine();

        $this->generatePrivacyPolicy();
        $this->newLine();
        $this->generateTermsOfService();
        $this->newLine();
        $this->generateAppDescription();
        $this->newLine();

        $this->info("🚀 **PASOS PARA HACER LA APP PÚBLICA:**");
        $this->line("=" . str_repeat("=", 35));
        $this->newLine();

        $this->line("1. Ve a https://developers.facebook.com/apps/");
        $this->line("2. Selecciona tu app");
        $this->line("3. Ve a 'Configuración' > 'Básica'");
        $this->line("4. Completa los campos requeridos:");
        $this->line("   • Política de privacidad (URL)");
        $this->line("   • Términos de servicio (URL)");
        $this->line("   • Descripción de la app");
        $this->line("5. Ve a 'Configuración' > 'Avanzada'");
        $this->line("6. Cambia 'Modo de la app' de 'Desarrollo' a 'Público'");
        $this->line("7. Completa la verificación de identidad");
        $this->newLine();

        $this->info("💡 **ALTERNATIVAS SI NO QUIERES HACERLA PÚBLICA:**");
        $this->line("=" . str_repeat("=", 45));
        $this->newLine();

        $this->line("✅ **Opción 1: Usar el sistema híbrido**");
        $this->line("   • Crear campañas y conjuntos de anuncios (funciona)");
        $this->line("   • Crear anuncios manualmente en el Administrador");
        $this->line("   • Usar el bot para configurar todo automáticamente");
        $this->newLine();

        $this->line("✅ **Opción 2: App de terceros**");
        $this->line("   • Usar una app ya verificada");
        $this->line("   • Menos control pero sin verificación");
        $this->newLine();

        $this->line("✅ **Opción 3: Verificación personal**");
        $this->line("   • Solo necesitas tu documento de identidad");
        $this->line("   • Proceso simple y rápido");
        $this->line("   • Te da control total");
        $this->newLine();

        $this->info("🎉 **RECOMENDACIÓN:**");
        $this->line("Te recomiendo hacer la verificación personal. Es simple,");
        $this->line("solo necesitas tu documento, y te da control total sobre");
        $this->line("tu bot de creación de campañas.");
        $this->newLine();

        return Command::SUCCESS;
    }

    private function generatePrivacyPolicy(): void
    {
        $this->info("🔒 **POLÍTICA DE PRIVACIDAD (Plantilla):**");
        $this->line("=" . str_repeat("=", 35));
        $this->newLine();

        $privacyPolicy = "
# Política de Privacidad

## Información que recopilamos
- Datos de campañas publicitarias que nos proporcionas
- Información de cuentas de Facebook conectadas
- Datos de uso del bot de Telegram

## Cómo usamos tu información
- Para crear y gestionar campañas publicitarias en Meta
- Para mejorar nuestros servicios
- Para comunicarnos contigo sobre tu cuenta

## Compartir información
No vendemos, alquilamos ni compartimos tu información personal con terceros.

## Seguridad
Implementamos medidas de seguridad para proteger tu información.

## Contacto
Para preguntas sobre esta política, contáctanos en: [tu-email@ejemplo.com]

Última actualización: " . date('Y-m-d');

        $this->line($privacyPolicy);
    }

    private function generateTermsOfService(): void
    {
        $this->info("📋 **TÉRMINOS DE SERVICIO (Plantilla):**");
        $this->line("=" . str_repeat("=", 35));
        $this->newLine();

        $termsOfService = "
# Términos de Servicio

## Aceptación de términos
Al usar este servicio, aceptas estos términos.

## Descripción del servicio
Bot de Telegram para automatizar la creación de campañas publicitarias en Meta.

## Uso aceptable
- No uses el servicio para actividades ilegales
- Respeta las políticas de Meta
- No abuses del sistema

## Limitaciones
- El servicio se proporciona 'tal como está'
- No garantizamos resultados específicos
- Puedes suspender el servicio en cualquier momento

## Modificaciones
Podemos modificar estos términos en cualquier momento.

## Contacto
Para preguntas sobre estos términos, contáctanos en: [tu-email@ejemplo.com]

Última actualización: " . date('Y-m-d');

        $this->line($termsOfService);
    }

    private function generateAppDescription(): void
    {
        $this->info("📱 **DESCRIPCIÓN DE LA APP:**");
        $this->line("=" . str_repeat("=", 25));
        $this->newLine();

        $appDescription = "
**Nombre:** Bot de Creación de Campañas Meta

**Descripción:** 
Bot de Telegram que automatiza la creación de campañas publicitarias en Meta (Facebook/Instagram). 
Permite a los usuarios crear campañas publicitarias de forma rápida y eficiente a través de 
conversaciones en Telegram, incluyendo configuración de targeting, presupuestos y creativos.

**Funcionalidades:**
- Creación automática de campañas publicitarias
- Configuración de targeting y audiencias
- Gestión de presupuestos
- Integración con cuentas de Meta
- Interfaz conversacional en Telegram

**Categoría:** Herramientas de marketing y publicidad

**Público objetivo:** Empresarios, marketers y anunciantes que usan Meta Ads";

        $this->line($appDescription);
    }
}
