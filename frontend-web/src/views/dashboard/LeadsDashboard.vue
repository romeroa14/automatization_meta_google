<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useLeadStore } from '@/stores/leadStore'
import { useOrganizationStore } from '@/stores/organizationStore'
import { useRouter } from 'vue-router'

const router = useRouter()
const leadStore = useLeadStore()
const orgStore = useOrganizationStore()
const loading = ref(false)
const selectedOrg = ref<number | null>(null)
const searchQuery = ref('')
const selectedLead = ref<any>(null)
const showChatDialog = ref(false)
const newMessage = ref('')

const filteredLeads = computed(() => {
  let leads = leadStore.leads

  if (selectedOrg.value) {
    leads = leads.filter(l => l.organization_id === selectedOrg.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    leads = leads.filter(l =>
      l.client_name?.toLowerCase().includes(query) ||
      l.phone_number?.includes(query)
    )
  }

  return leads
})

const stats = computed(() => ({
  total: filteredLeads.value.length,
  hot: filteredLeads.value.filter(l => l.lead_level === 'hot').length,
  warm: filteredLeads.value.filter(l => l.lead_level === 'warm').length,
  cold: filteredLeads.value.filter(l => l.lead_level === 'cold').length
}))

onMounted(async () => {
  loading.value = true
  await Promise.all([
    leadStore.fetchLeads(),
    orgStore.fetchOrganizations()
  ])
  loading.value = false
})

const getLevelColor = (level: string) => {
  const colors: Record<string, string> = {
    hot: 'error',
    warm: 'warning',
    cold: 'info'
  }
  return colors[level] || 'grey'
}

const getLevelIcon = (level: string) => {
  const icons: Record<string, string> = {
    hot: 'fire',
    warm: 'thermometer',
    cold: 'snowflake'
  }
  return icons[level] || 'account'
}

const getStageColor = (stage: string) => {
  const colors: Record<string, string> = {
    nuevo: 'primary',
    interesado: 'info',
    negociacion: 'warning',
    ganado: 'success',
    perdido: 'error'
  }
  return colors[stage] || 'grey'
}

const openChat = async (lead: any) => {
  selectedLead.value = lead
  showChatDialog.value = true
  await leadStore.fetchConversations(lead.id)
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || !selectedLead.value) return

  try {
    await leadStore.sendMessage(selectedLead.value.id, newMessage.value)
    newMessage.value = ''
  } catch (error) {
    console.error('Error sending message:', error)
  }
}

const formatTime = (date: string) => {
  return new Date(date).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}
</script>

<template>
  <div>
    <v-row class="mb-6">
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">💬 Leads & Conversaciones</h1>
            <p class="text-subtitle-1 text-medium-emphasis">
              Gestiona tus leads y conversaciones de WhatsApp en tiempo real
            </p>
          </div>
        </div>
      </v-col>
    </v-row>

    <v-row class="mb-6">
      <v-col cols="12" sm="6" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-medium-emphasis mb-1">Total Leads</p>
                <h2 class="text-h3 font-weight-bold">{{ stats.total }}</h2>
              </div>
              <v-avatar color="primary" size="56">
                <v-icon size="32">mdi-account-multiple</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-medium-emphasis mb-1">Hot Leads</p>
                <h2 class="text-h3 font-weight-bold text-error">{{ stats.hot }}</h2>
              </div>
              <v-avatar color="error" size="56">
                <v-icon size="32">mdi-fire</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-medium-emphasis mb-1">Warm Leads</p>
                <h2 class="text-h3 font-weight-bold text-warning">{{ stats.warm }}</h2>
              </div>
              <v-avatar color="warning" size="56">
                <v-icon size="32">mdi-thermometer</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card class="stat-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <p class="text-caption text-medium-emphasis mb-1">Cold Leads</p>
                <h2 class="text-h3 font-weight-bold text-info">{{ stats.cold }}</h2>
              </div>
              <v-avatar color="info" size="56">
                <v-icon size="32">mdi-snowflake</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mb-4">
      <v-col cols="12" md="6">
        <v-text-field
          v-model="searchQuery"
          prepend-inner-icon="mdi-magnify"
          label="Buscar leads..."
          variant="outlined"
          clearable
          hide-details
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-select
          v-model="selectedOrg"
          :items="[
            { title: 'Todas las organizaciones', value: null },
            ...orgStore.organizations.map(o => ({ title: o.name, value: o.id }))
          ]"
          prepend-inner-icon="mdi-domain"
          label="Filtrar por organización"
          variant="outlined"
          hide-details
        ></v-select>
      </v-col>
    </v-row>

    <v-row v-if="loading">
      <v-col v-for="i in 6" :key="i" cols="12" md="6" lg="4">
        <v-skeleton-loader type="card"></v-skeleton-loader>
      </v-col>
    </v-row>

    <v-row v-else-if="filteredLeads.length === 0">
      <v-col cols="12">
        <v-card class="text-center pa-12" elevation="0" color="grey-lighten-4">
          <v-icon size="80" color="grey-lighten-1" class="mb-4">mdi-account-off</v-icon>
          <h3 class="text-h5 mb-2">No hay leads</h3>
          <p class="text-body-1 text-medium-emphasis">
            Los leads aparecerán aquí cuando recibas mensajes de WhatsApp
          </p>
        </v-card>
      </v-col>
    </v-row>

    <v-row v-else>
      <v-col
        v-for="lead in filteredLeads"
        :key="lead.id"
        cols="12"
        md="6"
        lg="4"
      >
        <v-card
          class="lead-card"
          elevation="2"
          hover
          @click="openChat(lead)"
        >
          <v-card-text>
            <div class="d-flex justify-space-between align-center mb-3">
              <v-chip
                :color="getLevelColor(lead.lead_level)"
                size="small"
                :prepend-icon="`mdi-${getLevelIcon(lead.lead_level)}`"
              >
                {{ lead.lead_level?.toUpperCase() }}
              </v-chip>
              <v-chip
                :color="getStageColor(lead.stage)"
                size="small"
                variant="flat"
              >
                {{ lead.stage }}
              </v-chip>
            </div>

            <div class="d-flex align-center mb-3">
              <v-avatar color="primary" size="48" class="mr-3">
                <v-icon size="32">mdi-account</v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <h4 class="text-h6 font-weight-bold">{{ lead.client_name || 'Sin nombre' }}</h4>
                <p class="text-body-2 text-medium-emphasis">
                  <v-icon size="16" class="mr-1">mdi-phone</v-icon>
                  {{ lead.phone_number }}
                </p>
              </div>
            </div>

            <v-divider class="my-3"></v-divider>

            <div v-if="lead.intent" class="mb-2">
              <v-chip size="small" variant="outlined" prepend-icon="mdi-bullseye">
                {{ lead.intent }}
              </v-chip>
            </div>

            <div class="d-flex align-center text-caption text-medium-emphasis">
              <v-icon size="16" class="mr-1">mdi-clock-outline</v-icon>
              {{ formatDate(lead.created_at) }}
            </div>

            <div v-if="lead.bot_disabled" class="mt-2">
              <v-chip color="warning" size="small" prepend-icon="mdi-robot-off">
                Bot Deshabilitado
              </v-chip>
            </div>
          </v-card-text>

          <v-card-actions>
            <v-btn
              variant="text"
              color="success"
              prepend-icon="mdi-whatsapp"
              @click.stop="openChat(lead)"
            >
              Ver Chat
            </v-btn>
            <v-spacer></v-spacer>
            <v-progress-circular
              v-if="lead.confidence_score"
              :model-value="lead.confidence_score * 100"
              :color="lead.confidence_score > 0.7 ? 'success' : 'warning'"
              size="32"
              width="4"
            >
              <span class="text-caption">{{ Math.round(lead.confidence_score * 100) }}</span>
            </v-progress-circular>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="showChatDialog" max-width="900" scrollable>
      <v-card v-if="selectedLead" class="chat-dialog">
        <v-card-title class="d-flex justify-space-between align-center bg-success pa-4">
          <div class="d-flex align-center text-white">
            <v-avatar color="white" size="40" class="mr-3">
              <v-icon color="success">mdi-account</v-icon>
            </v-avatar>
            <div>
              <h3 class="text-h6">{{ selectedLead.client_name || 'Sin nombre' }}</h3>
              <p class="text-caption">{{ selectedLead.phone_number }}</p>
            </div>
          </div>
          <v-btn icon="mdi-close" variant="text" color="white" @click="showChatDialog = false"></v-btn>
        </v-card-title>

        <v-divider></v-divider>

        <v-card-text class="chat-container pa-4" style="height: 500px; overflow-y: auto;">
          <div v-if="leadStore.conversations.length === 0" class="text-center pa-8">
            <v-icon size="64" color="grey-lighten-1">mdi-message-off</v-icon>
            <p class="text-body-1 text-medium-emphasis mt-4">No hay conversaciones aún</p>
          </div>

          <div v-else class="messages-list">
            <div
              v-for="conv in leadStore.conversations"
              :key="conv.id"
              class="message-wrapper mb-4"
            >
              <div v-if="conv.message_text" class="message client-message">
                <div class="message-bubble">
                  <p class="message-text">{{ conv.message_text }}</p>
                  <span class="message-time">{{ formatTime(conv.timestamp || conv.created_at) }}</span>
                </div>
              </div>

              <div v-if="conv.response" class="message bot-message">
                <div class="message-bubble">
                  <p class="message-text">{{ conv.response }}</p>
                  <span class="message-time">{{ formatTime(conv.timestamp || conv.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions class="pa-4">
          <v-text-field
            v-model="newMessage"
            placeholder="Escribe un mensaje..."
            variant="outlined"
            hide-details
            density="comfortable"
            @keyup.enter="sendMessage"
          >
            <template #prepend-inner>
              <v-icon>mdi-emoticon-happy-outline</v-icon>
            </template>
            <template #append-inner>
              <v-btn
                icon="mdi-send"
                color="success"
                variant="flat"
                size="small"
                :disabled="!newMessage.trim()"
                @click="sendMessage"
              ></v-btn>
            </template>
          </v-text-field>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped>
.stat-card {
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
}

.lead-card {
  cursor: pointer;
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.lead-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
  border-left-color: rgb(var(--v-theme-success));
}

.chat-container {
  background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
}

.messages-list {
  display: flex;
  flex-direction: column;
}

.message-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.message {
  display: flex;
  max-width: 70%;
}

.client-message {
  align-self: flex-start;
}

.client-message .message-bubble {
  background: white;
  border-radius: 0 12px 12px 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.bot-message {
  align-self: flex-end;
}

.bot-message .message-bubble {
  background: #dcf8c6;
  border-radius: 12px 0 12px 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.message-bubble {
  padding: 12px 16px;
  position: relative;
}

.message-text {
  margin: 0;
  word-wrap: break-word;
  font-size: 14px;
  line-height: 1.4;
}

.message-time {
  display: block;
  font-size: 11px;
  color: rgba(0, 0, 0, 0.45);
  margin-top: 4px;
  text-align: right;
}
</style>
