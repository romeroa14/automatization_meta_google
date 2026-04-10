<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Workspace;
use App\Models\WhatsappInstance;
use App\Models\Lead;
use App\Models\Message;
use App\Models\WorkspaceFacebookConnection;
use Carbon\Carbon;

class MigrateToTenantsCommand extends Command
{
    protected $signature = 'tenant:migrate-data';
    protected $description = 'Migrate data from legacy tables to new tenant structure';

    public function handle()
    {
        $this->info('Starting data migration to Multi-Tenant structure...');

        // 1. Migrar Organizations -> Workspaces
        if (Schema::hasTable('organizations')) {
            $organizations = DB::table('organizations')->get();
            $this->info("Migrating {$organizations->count()} organizations to workspaces...");
            foreach ($organizations as $org) {
                $o = (array) $org;
                DB::table('workspaces')->insert([
                    'id' => $o['id'],
                    'name' => $o['name'] ?? 'Workspace',
                    'slug' => $o['slug'] ?? 'workspace-'.$o['id'],
                    'description' => $o['description'] ?? null,
                    'logo_url' => $o['logo_url'] ?? null,
                    'website' => $o['website'] ?? null,
                    'email' => $o['email'] ?? null,
                    'phone' => $o['phone'] ?? null,
                    'settings' => $o['settings'] ?? null,
                    'plan_type' => $o['plan'] ?? 'ads_client',
                    'n8n_webhook_url' => $o['n8n_webhook_url'] ?? null,
                    'is_active' => $o['is_active'] ?? true,
                    'trial_ends_at' => $o['trial_ends_at'] ?? null,
                    'created_at' => $o['created_at'] ?? now(),
                    'updated_at' => $o['updated_at'] ?? now(),
                ]);

                // Asignar usuarios dueños al workspace_user
                $orgUsers = DB::table('organization_user')->where('organization_id', $o['id'])->get();
                foreach ($orgUsers as $ou) {
                    $u = (array) $ou;
                    DB::table('workspace_user')->insert([
                        'workspace_id' => $o['id'],
                        'user_id' => $u['user_id'],
                        'role' => $u['role'] ?? 'viewer',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2. Migrar Whatsapp Phone Numbers -> Whatsapp Instances
        if (Schema::hasTable('whatsapp_phone_numbers')) {
            $phones = DB::table('whatsapp_phone_numbers')->get();
            $this->info("Migrating {$phones->count()} whatsapp numbers to instances...");
            foreach ($phones as $phone) {
                $p = (array) $phone;
                DB::table('whatsapp_instances')->insert([
                    'id' => $p['id'],
                    'workspace_id' => $p['organization_id'] ?? 1,
                    'name' => $p['name'] ?? null,
                    'phone_number' => $p['phone_number'],
                    'phone_number_id' => $p['phone_number_id'] ?? null,
                    'waba_id' => $p['waba_id'] ?? null,
                    'access_token' => $p['access_token'] ?? null,
                    'webhook_verify_token' => $p['webhook_verify_token'] ?? null,
                    'is_default' => $p['is_default'] ?? false,
                    'status' => $p['status'] ?? 'disconnected',
                    'created_at' => $p['created_at'] ?? now(),
                    'updated_at' => $p['updated_at'] ?? now(),
                ]);
            }
        }

        // 3. Migrar Legacy Leads -> Leads
        if (Schema::hasTable('legacy_leads')) {
            $leads = DB::table('legacy_leads')->get();
            $this->info("Migrating {$leads->count()} leads...");
            foreach ($leads as $oldLead) {
                $l = (array) $oldLead;
                DB::table('leads')->insert([
                    'id' => $l['id'],
                    'workspace_id' => $l['organization_id'] ?? 1, 
                    'whatsapp_instance_id' => $l['whatsapp_phone_number_id'] ?? null,
                    'phone_number' => $l['phone_number'],
                    'client_name' => $l['client_name'] ?? null,
                    'intent' => $l['intent'] ?? null,
                    'lead_level' => $l['lead_level'] ?? null,
                    'stage' => $l['stage'] ?? 'new',
                    'confidence_score' => $l['confidence_score'] ?? null,
                    'bot_disabled' => $l['bot_disabled'] ?? false,
                    'last_human_intervention_at' => $l['last_human_intervention_at'] ?? null,
                    'created_at' => $l['created_at'] ?? now(),
                    'updated_at' => $l['updated_at'] ?? now(),
                ]);
            }
        }

        // 4. Migrar Legacy Conversations -> Messages
        if (Schema::hasTable('legacy_conversations')) {
            $conversations = DB::table('legacy_conversations')->orderBy('id')->get();
            $this->info("Migrating {$conversations->count()} conversations to messages...");
            foreach ($conversations as $conv) {
                $c = (array) $conv;
                $isClient = $c['is_client_message'] ?? true;
                $content = $isClient ? ($c['message_text'] ?? null) : ($c['response'] ?? null);
                
                // Si ambos tienen contenido o ninguno (por si hay data extraña)
                if (empty($content) && !empty($c['response'])) {
                    $content = $c['response'];
                    $isClient = false;
                }
                
                DB::table('messages')->insert([
                    'id' => $c['id'],
                    'lead_id' => $c['lead_id'],
                    'user_id' => $c['user_id'] ?? null,
                    'message_id' => $c['message_id'] ?? null,
                    'direction' => $isClient ? 'inbound' : 'outbound',
                    'is_client_message' => $isClient,
                    'is_employee' => $c['is_employee'] ?? false,
                    'content' => $content ?: 'N/A', 
                    'platform' => $c['platform'] ?? 'whatsapp',
                    'status' => $c['status'] ?? 'sent',
                    'message_length' => $c['message_length'] ?? 0,
                    'handled_by_ai' => !($c['is_employee'] ?? false) && !$isClient, 
                    'timestamp' => $c['timestamp'] ?? null,
                    'created_at' => $c['created_at'] ?? now(),
                    'updated_at' => $c['updated_at'] ?? now(),
                ]);
            }
        }

        // 5. Migrar User Facebook Connections -> Workspace Facebook Connections
        if (Schema::hasTable('user_facebook_connections')) {
            $fbConns = DB::table('user_facebook_connections')->get();
            $this->info("Migrating {$fbConns->count()} FB Connections to workspaces...");
            foreach ($fbConns as $fbConn) {
                $fb = (array) $fbConn;
                // Buscamos un workspace_id relacionado a este usuario
                $pivot = DB::table('workspace_user')->where('user_id', $fb['user_id'])->first();
                $workspaceId = $pivot ? $pivot->workspace_id : null;

                if ($workspaceId) {
                    DB::table('workspace_facebook_connections')->insert([
                        'id' => $fb['id'],
                        'workspace_id' => $workspaceId,
                        'facebook_user_id' => $fb['facebook_user_id'],
                        'facebook_name' => $fb['facebook_name'] ?? null,
                        'facebook_email' => $fb['facebook_email'] ?? null,
                        'access_token' => $fb['access_token'] ?? '',
                        'token_expires_at' => $fb['token_expires_at'] ?? null,
                        'scopes' => $fb['scopes'] ?? null,
                        'ad_accounts' => $fb['ad_accounts'] ?? null,
                        'pages' => $fb['pages'] ?? null,
                        'selected_ad_account_id' => $fb['selected_ad_account_id'] ?? null,
                        'selected_page_id' => $fb['selected_page_id'] ?? null,
                        'is_active' => $fb['is_active'] ?? true,
                        'last_used_at' => $fb['last_used_at'] ?? null,
                        'created_at' => $fb['created_at'] ?? now(),
                        'updated_at' => $fb['updated_at'] ?? now(),
                    ]);
                } else {
                    $this->warn("FB Connection ID {$fb['id']} skipped - No workspace found for User ID {$fb['user_id']}");
                }
            }
        }

        // Restart PSequences (Por si serializamos desde el ID exacto y la BD es PostgreSQL)
        DB::statement('SELECT setval(\'workspaces_id_seq\', (SELECT MAX(id) FROM workspaces));');
        DB::statement('SELECT setval(\'whatsapp_instances_id_seq\', (SELECT MAX(id) FROM whatsapp_instances));');
        DB::statement('SELECT setval(\'leads_id_seq\', (SELECT MAX(id) FROM leads));');
        DB::statement('SELECT setval(\'messages_id_seq\', (SELECT MAX(id) FROM messages));');
        DB::statement('SELECT setval(\'workspace_facebook_connections_id_seq\', (SELECT MAX(id) FROM workspace_facebook_connections));');

        $this->info('Migration completed successfully! Legacy data preserved in tables prefixed with "legacy_" y "organizations".');
    }
}
