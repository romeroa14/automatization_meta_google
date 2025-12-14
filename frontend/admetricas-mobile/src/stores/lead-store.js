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
                this.conversations = response.data.data
            } catch (error) {
                console.error('Error fetching conversations:', error)
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
