<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsAppPhoneNumber;
use Illuminate\Console\Command;

class SetupOrganizationCommand extends Command
{
    protected $signature = 'org:setup
                            {--name= : Organization name}
                            {--email= : Admin user email}
                            {--phone= : WhatsApp phone number}
                            {--phone-id= : Meta Phone Number ID}
                            {--waba-id= : WhatsApp Business Account ID}
                            {--token= : Access Token}
                            {--verify-token= : Verify Token}
                            {--n8n-url= : n8n Webhook URL}';

    protected $description = 'Setup a new organization with WhatsApp number';

    public function handle()
    {
        $this->info('🚀 Configurando organización...');
        
        // Obtener datos
        $orgName = $this->option('name') ?? $this->ask('Nombre de la organización', 'Admetricas Agency');
        $userEmail = $this->option('email') ?? $this->ask('Email del usuario admin', 'admin@admetricas.com');
        $phoneNumber = $this->option('phone') ?? $this->ask('Número de WhatsApp (formato E.164)', '+584222635796');
        $phoneNumberId = $this->option('phone-id') ?? $this->ask('Phone Number ID de Meta');
        $wabaId = $this->option('waba-id') ?? $this->ask('WABA ID');
        $accessToken = $this->option('token') ?? $this->secret('Access Token');
        $verifyToken = $this->option('verify-token') ?? $this->ask('Verify Token (opcional)', 'admetricas_verify_token');
        $n8nUrl = $this->option('n8n-url') ?? $this->ask('URL del webhook de n8n (opcional)');
        
        // Buscar o crear usuario
        $user = User::where('email', $userEmail)->first();
        
        if (!$user) {
            $this->warn("Usuario {$userEmail} no encontrado. Creando...");
            $password = $this->secret('Password para el nuevo usuario');
            $user = User::create([
                'name' => explode('@', $userEmail)[0],
                'email' => $userEmail,
                'password' => bcrypt($password),
            ]);
            $this->info("✅ Usuario creado: {$user->email}");
        } else {
            $this->info("✅ Usuario encontrado: {$user->email}");
        }
        
        // Buscar o crear organización
        $slug = \Illuminate\Support\Str::slug($orgName);
        $organization = Organization::withTrashed()->where('slug', $slug)->first();
        
        if ($organization) {
            if ($organization->trashed()) {
                $this->warn("⚠️  Organización '{$orgName}' existe pero está eliminada. Restaurando...");
                $organization->restore();
                $organization->update([
                    'name' => $orgName,
                    'plan' => 'enterprise',
                    'is_active' => true,
                    'n8n_webhook_url' => $n8nUrl ?: null,
                ]);
                $this->info("✅ Organización restaurada: {$organization->name} (ID: {$organization->id})");
            } else {
                $this->warn("⚠️  Organización '{$orgName}' ya existe (ID: {$organization->id})");
                $updateOrg = $this->confirm('¿Deseas actualizar la organización existente?', true);
                if ($updateOrg) {
                    $organization->update([
                        'n8n_webhook_url' => $n8nUrl ?: $organization->n8n_webhook_url,
                    ]);
                    $this->info("✅ Organización actualizada: {$organization->name} (ID: {$organization->id})");
                }
            }
        } else {
            $organization = Organization::create([
                'name' => $orgName,
                'slug' => $slug,
                'plan' => 'enterprise',
                'is_active' => true,
                'n8n_webhook_url' => $n8nUrl ?: null,
            ]);
            $this->info("✅ Organización creada: {$organization->name} (ID: {$organization->id})");
        }
        
        // Asociar usuario como owner
        $organization->users()->attach($user->id, ['role' => 'owner']);
        $this->info("✅ Usuario {$user->email} asignado como OWNER");
        
        // Crear número de WhatsApp
        $whatsappNumber = WhatsAppPhoneNumber::create([
            'organization_id' => $organization->id,
            'phone_number' => $phoneNumber,
            'display_name' => $orgName,
            'phone_number_id' => $phoneNumberId,
            'waba_id' => $wabaId,
            'access_token' => $accessToken, // Se encripta automáticamente
            'verify_token' => $verifyToken,
            'webhook_url' => config('app.url') . '/api/webhook/whatsapp',
            'status' => 'active',
            'quality_rating' => 'green',
            'is_default' => true,
            'verified_at' => now(),
        ]);
        
        $this->info("✅ Número de WhatsApp agregado: {$whatsappNumber->phone_number}");
        
        // Resumen
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🎉 CONFIGURACIÓN COMPLETADA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Organización ID', $organization->id],
                ['Organización', $organization->name],
                ['Plan', $organization->plan],
                ['Usuario Admin', $user->email],
                ['Rol', 'owner'],
                ['Número WhatsApp', $whatsappNumber->phone_number],
                ['Phone Number ID', $whatsappNumber->phone_number_id],
                ['WABA ID', $whatsappNumber->waba_id],
                ['Estado', $whatsappNumber->status],
                ['Calidad', $whatsappNumber->quality_rating],
                ['Predeterminado', $whatsappNumber->is_default ? 'Sí' : 'No'],
                ['Webhook URL', $whatsappNumber->webhook_url],
                ['n8n URL', $organization->n8n_webhook_url ?? 'No configurado'],
            ]
        );
        
        $this->newLine();
        $this->info('📝 Próximos pasos:');
        $this->line('1. Configura el webhook en Meta: ' . $whatsappNumber->webhook_url);
        $this->line('2. Usa el verify_token: ' . $verifyToken);
        $this->line('3. Los mensajes se asociarán automáticamente a esta organización');
        $this->line('4. Accede al panel de Filament: ' . config('app.url') . '/admin');
        
        return Command::SUCCESS;
    }
}
