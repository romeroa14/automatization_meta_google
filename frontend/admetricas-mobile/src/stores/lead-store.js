import { defineStore } from 'pinia'
import { api } from 'boot/axios'

export const useLeadStore = defineStore('leads', {
    state: () => ({
        leads: [],
        currentLead: null,
        conversations: [],
        loading: false,
        pagination: {
            page: 1,
            rowsPerPage: 20,
            rowsNumber: 0
        }
    }),
    actions: {
        async fetchLeads(page = 1) {
            this.loading = true
            console.log('[LeadStore] Fetching leads page:', page)
            try {
                const response = await api.get(`/leads?page=${page}`)
                console.log('[LeadStore] Leads loaded:', response.data)
                this.leads = response.data.data
                this.pagination.page = response.data.meta.current_page
                this.pagination.rowsNumber = response.data.meta.total
            } catch (error) {
                console.error('[LeadStore] Error fetching leads. Check Network/Console.', error)
                if (error.response?.status === 401) {
                    console.warn('[LeadStore] Unauthorized. User needs to login.')
                }
            } finally {
                this.loading = false
            }
        },
        async fetchLead(id) {
            this.loading = true
            try {
                const response = await api.get(`/leads/${id}`)
                this.currentLead = response.data.data
            } catch (error) {
                console.error('Error fetching lead:', error)
            } finally {
                this.loading = false
            }
        },
        async fetchConversations(leadId) {
            try {
                const response = await api.get(`/leads/${leadId}/conversations`)
                console.log('[LeadStore] 📥 Conversations fetched from API:', {
                    count: response.data.data?.length,
                    raw_response: response.data,
                    full_response: JSON.stringify(response.data, null, 2),
                })
                
                // Verificar estructura de respuesta
                if (response.data.data) {
                    console.log('[LeadStore] ✅ Response has data array')
                } else if (response.data) {
                    console.log('[LeadStore] ⚠️ Response structure different, using response.data directly')
                    this.conversations = Array.isArray(response.data) ? response.data : []
                } else {
                    console.error('[LeadStore] ❌ No data in response!', response)
                    this.conversations = []
                    return
                }
                
                this.conversations = response.data.data || []
                
                // Log DETALLADO para debug: verificar respuestas del bot
                console.log('[LeadStore] 📊 All conversations:', this.conversations.map(c => ({
                    id: c.id,
                    is_client_message: c.is_client_message,
                    is_employee: c.is_employee,
                    has_response: !!c.response,
                    response_length: c.response?.length || 0,
                    has_message_text: !!c.message_text,
                    message_text_length: c.message_text?.length || 0,
                    response_preview: c.response?.substring(0, 100),
                    message_text_preview: c.message_text?.substring(0, 100),
                })))
                
                const botResponses = this.conversations.filter(c => !c.is_client_message)
                console.log('[LeadStore] 🤖 Bot responses found:', {
                    count: botResponses.length,
                    responses: botResponses.map(r => ({
                        id: r.id,
                        is_client_message: r.is_client_message,
                        is_employee: r.is_employee,
                        has_response: !!r.response,
                        response_length: r.response?.length || 0,
                        has_message_text: !!r.message_text,
                        message_text_length: r.message_text?.length || 0,
                        response_value: r.response,
                        message_text_value: r.message_text,
                        response_preview: r.response?.substring(0, 100),
                        message_text_preview: r.message_text?.substring(0, 100),
                    })),
                })
                
                // Verificar si hay conversaciones sin contenido visible
                const emptyBotResponses = botResponses.filter(r => !r.response && !r.message_text)
                if (emptyBotResponses.length > 0) {
                    console.error('[LeadStore] ❌ Bot responses WITHOUT content:', emptyBotResponses)
                }
            } catch (error) {
                console.error('[LeadStore] ❌ Error fetching conversations:', error)
            }
        },
        async updateLeadStage(leadId, newStage) {
            try {
                const response = await api.patch(`/leads/${leadId}`, { stage: newStage })
                console.log('[LeadStore] Lead stage updated:', response.data)
                // Update local state
                const leadIndex = this.leads.findIndex(l => l.id === leadId)
                if (leadIndex !== -1) {
                    this.leads[leadIndex].stage = newStage
                }
                return true
            } catch (error) {
                console.error('[LeadStore] Error updating lead stage:', error)
                return false
            }
        },
        async sendMessage(leadId, message) {
            try {
                // Optimistic update handled in component, or we can do it here. 
                // For now, let's just send the request.
                // NOTE: We assume the backend has this endpoint (we need to create it later as per plan)
                // const response = await api.post(`/leads/${leadId}/messages`, { message })
                // this.conversations.push(response.data.data)
                console.log('Sending message:', message)
                return true
            } catch (error) {
                console.error('Error sending message:', error)
                return false
            }
        }
    },
})
