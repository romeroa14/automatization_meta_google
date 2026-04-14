import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/plugins/axios'

export interface Lead {
  id: number
  client_name: string
  phone_number: string
  organization_id?: number
  stage: string
  platform: string
  confidence_score?: number
  intent?: string
  lead_level?: string
  bot_disabled?: boolean
  created_at: string
}

export interface Conversation {
  id: number
  lead_id: number
  message_text: string | null
  response: string | null
  timestamp: string
  platform: string
  is_client_message: boolean
  is_employee: boolean
  created_at?: string
}

export const useLeadStore = defineStore('lead', () => {
  const leads = ref<Lead[]>([])
  const currentLead = ref<Lead | null>(null)
  const conversations = ref<Conversation[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch all leads
  async function fetchLeads() {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get('/leads')
      leads.value = response.data.data || response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar leads'
      console.error('Error fetching leads:', err)
    } finally {
      loading.value = false
    }
  }

  // Fetch single lead
  async function fetchLead(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get(`/leads/${id}`)
      currentLead.value = response.data.data || response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar lead'
      console.error('Error fetching lead:', err)
    } finally {
      loading.value = false
    }
  }

  // Fetch conversations for a lead
  async function fetchConversations(leadId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get(`/leads/${leadId}/conversations`)
      conversations.value = response.data.data || response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar conversaciones'
      console.error('Error fetching conversations:', err)
    } finally {
      loading.value = false
    }
  }

  // Send WhatsApp message
  async function sendMessage(leadId: number, message: string) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post('/whatsapp/send', {
        lead_id: leadId,
        message: message
      })
      // Refresh conversations after sending
      await fetchConversations(leadId)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al enviar mensaje'
      console.error('Error sending message:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update lead stage
  async function updateLeadStage(leadId: number, stage: string) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put(`/leads/${leadId}`, { stage })
      if (currentLead.value?.id === leadId) {
        currentLead.value.stage = stage
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al actualizar lead'
      console.error('Error updating lead:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Toggle Bot status
  async function toggleBot(leadId: number, botDisabled: boolean) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post('/whatsapp/toggle-bot', {
        lead_id: leadId,
        bot_disabled: botDisabled
      })
      if (currentLead.value?.id === leadId) {
        currentLead.value.bot_disabled = botDisabled
      }
      
      const leadIndex = leads.value.findIndex(l => l.id === leadId)
      if (leadIndex !== -1 && leads.value[leadIndex]) {
        leads.value[leadIndex]!.bot_disabled = botDisabled
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cambiar estado del bot'
      console.error('Error toggling bot:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    leads,
    currentLead,
    conversations,
    loading,
    error,
    fetchLeads,
    fetchLead,
    fetchConversations,
    sendMessage,
    updateLeadStage,
    toggleBot
  }
})
