import { defineStore } from 'pinia'
import { api } from 'boot/axios'

export const useCampaignStore = defineStore('campaigns', {
    state: () => ({
        campaigns: [],
        loading: false,
    }),
    actions: {
        async fetchCampaigns() {
            this.loading = true
            try {
                const response = await api.get('/campaigns')
                this.campaigns = response.data.data
            } catch (error) {
                console.error('Error fetching campaigns:', error)
            } finally {
                this.loading = false
            }
        }
    },
})
