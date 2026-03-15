<template>
  <v-app>
    <v-app-bar color="primary" prominent>
      <v-btn icon @click="$router.back()">
        <v-icon>mdi-arrow-left</v-icon>
      </v-btn>
      
      <v-avatar color="secondary" class="ml-3">
        <span class="text-h6">{{ clientInitial }}</span>
      </v-avatar>
      
      <v-toolbar-title class="ml-3">
        <div class="text-h6">{{ leadStore.currentLead?.client_name || 'Cargando...' }}</div>
        <div class="text-caption">{{ leadStore.currentLead?.intent || 'En línea' }}</div>
      </v-toolbar-title>
      
      <v-spacer></v-spacer>
      
      <v-btn icon>
        <v-icon>mdi-video</v-icon>
      </v-btn>
      <v-btn icon>
        <v-icon>mdi-phone</v-icon>
      </v-btn>
      <v-btn icon>
        <v-icon>mdi-dots-vertical</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main class="chat-background">
      <v-container fluid class="fill-height pa-4">
        <!-- Date Divider -->
        <v-row justify="center" class="mb-4">
          <v-chip color="grey-lighten-2" size="small">Hoy</v-chip>
        </v-row>

        <!-- Messages -->
        <div v-if="!leadStore.loading && leadStore.conversations.length > 0">
          <template v-for="conv in leadStore.conversations" :key="conv.id">
            <!-- Client Message (message_text) - White bubble, left -->
            <v-row v-if="conv.message_text" justify="start" class="mb-3">
              <v-col cols="auto" class="py-0" style="max-width: 75%">
                <v-card class="chat-bubble-client" elevation="1">
                  <v-card-text class="pb-1">
                    <div class="text-body-2" v-html="formatMessage(conv.message_text)"></div>
                    <div class="text-caption text-right mt-1" style="opacity: 0.7">
                      {{ formatTime(conv.timestamp) }}
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- Bot Response (response) - Green bubble, right -->
            <v-row v-if="conv.response" justify="end" class="mb-3">
              <v-col cols="auto" class="py-0" style="max-width: 75%">
                <v-card class="chat-bubble-bot" elevation="1">
                  <v-card-text class="pb-1">
                    <div class="text-body-2" v-html="formatMessage(conv.response)"></div>
                    <div class="text-caption text-right mt-1" style="opacity: 0.7">
                      {{ formatTime(conv.timestamp) }}
                      <v-icon size="14" color="blue" class="ml-1">mdi-check-all</v-icon>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </template>
        </div>

        <!-- Loading State -->
        <v-row v-else-if="leadStore.loading" justify="center" class="fill-height">
          <v-col cols="auto" class="text-center">
            <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
            <div class="mt-4">Cargando conversación...</div>
          </v-col>
        </v-row>

        <!-- Empty State -->
        <v-row v-else justify="center" class="fill-height">
          <v-col cols="auto" class="text-center">
            <v-icon size="64" color="grey">mdi-chat-outline</v-icon>
            <div class="mt-4 text-h6">Inicia la conversación</div>
            <div class="text-caption">Los mensajes se sincronizarán con WhatsApp</div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Input Footer -->
    <v-footer app class="bg-grey-lighten-4 pa-2">
      <v-row align="center" no-gutters>
        <v-col cols="auto">
          <v-btn icon variant="text">
            <v-icon>mdi-plus</v-icon>
          </v-btn>
        </v-col>
        
        <v-col>
          <v-text-field
            v-model="newMessage"
            placeholder="Escribe un mensaje"
            variant="outlined"
            density="comfortable"
            hide-details
            rounded
            bg-color="white"
            @keydown.enter.prevent="sendMessage"
          >
            <template v-slot:append-inner>
              <v-icon class="mr-2">mdi-paperclip</v-icon>
              <v-icon v-if="!newMessage">mdi-camera</v-icon>
            </template>
          </v-text-field>
        </v-col>
        
        <v-col cols="auto">
          <v-btn
            icon
            :color="newMessage ? 'primary' : 'teal'"
            @click="sendMessage"
            class="ml-2"
          >
            <v-icon>{{ newMessage ? 'mdi-send' : 'mdi-microphone' }}</v-icon>
          </v-btn>
        </v-col>
      </v-row>
    </v-footer>
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLeadStore } from '@/stores/leadStore'

const route = useRoute()
const router = useRouter()
const leadStore = useLeadStore()

const newMessage = ref('')
const leadId = computed(() => parseInt(route.params.id as string))

const clientInitial = computed(() => {
  return leadStore.currentLead?.client_name?.charAt(0).toUpperCase() || '?'
})

const formatMessage = (text: string) => {
  if (!text) return ''
  // Decode escaped characters
  return text
    .replace(/\\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
}

const formatTime = (timestamp: string) => {
  if (!timestamp) return ''
  const date = new Date(timestamp.replace(/"/g, ''))
  return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
}

const sendMessage = async () => {
  if (!newMessage.value.trim()) return
  
  try {
    await leadStore.sendMessage(leadId.value, newMessage.value)
    newMessage.value = ''
  } catch (error) {
    console.error('Error sending message:', error)
  }
}

onMounted(async () => {
  await leadStore.fetchLead(leadId.value)
  await leadStore.fetchConversations(leadId.value)
})
</script>

<style scoped>
.chat-background {
  background-color: #e5ddd5;
  background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
}

.chat-bubble-client {
  background-color: #ffffff !important;
  border-radius: 8px;
}

.chat-bubble-bot {
  background-color: #dcf8c6 !important;
  border-radius: 8px;
}
</style>
