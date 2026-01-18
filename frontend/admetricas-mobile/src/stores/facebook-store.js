import { defineStore } from 'pinia'
import { api } from 'boot/axios'

export const useFacebookStore = defineStore('facebook', {
    state: () => ({
        isConnected: false,
        isLoading: false,
        connectionData: null,
        adAccounts: [],
        pages: [],
        campaigns: [],
        selectedAdAccountId: null,
        selectedPageId: null,
        error: null,
    }),

    getters: {
        hasAdAccounts: (state) => state.adAccounts.length > 0,
        hasPages: (state) => state.pages.length > 0,
        needsRenewal: (state) => state.connectionData?.needs_renewal || false,
    },

    actions: {
        /**
         * Obtener URL de login de Facebook
         */
        async getLoginUrl() {
            this.isLoading = true
            this.error = null

            try {
                const response = await api.get('/auth/facebook/login-url')
                return response.data.login_url
            } catch (error) {
                console.error('[FacebookStore] Error getting login URL:', error)
                this.error = error.response?.data?.error || 'Error obteniendo URL de login'
                throw error
            } finally {
                this.isLoading = false
            }
        },

        /**
         * Manejar callback de Facebook (enviar code al backend)
         */
        async handleCallback(code) {
            this.isLoading = true
            this.error = null

            try {
                const response = await api.post('/auth/facebook/callback', { code })

                if (response.data.success) {
                    this.isConnected = true
                    this.connectionData = response.data.connection
                    this.adAccounts = response.data.connection.ad_accounts || []
                    this.pages = response.data.connection.pages || []

                    // Persistir estado
                    localStorage.setItem('fb_connected', 'true')

                    return response.data
                } else {
                    throw new Error(response.data.error || 'Error desconocido')
                }
            } catch (error) {
                console.error('[FacebookStore] Callback error:', error)
                this.error = error.response?.data?.error || error.message
                throw error
            } finally {
                this.isLoading = false
            }
        },

        /**
         * Verificar estado de conexión actual
         */
        async checkConnectionStatus() {
            this.isLoading = true

            try {
                const response = await api.get('/auth/facebook/status')

                this.isConnected = response.data.connected
                if (response.data.connected) {
                    this.connectionData = {
                        facebook_name: response.data.facebook_name,
                        token_expires_at: response.data.token_expires_at,
                        needs_renewal: response.data.needs_renewal,
                    }
                    this.adAccounts = response.data.ad_accounts || []
                    this.pages = response.data.pages || []
                    this.selectedAdAccountId = response.data.selected_ad_account_id
                    this.selectedPageId = response.data.selected_page_id
                }

                return response.data
            } catch (error) {
                console.error('[FacebookStore] Status check error:', error)
                this.isConnected = false
                return { connected: false }
            } finally {
                this.isLoading = false
            }
        },

        /**
         * Desconectar cuenta de Facebook
         */
        async disconnect() {
            this.isLoading = true
            this.error = null

            try {
                await api.post('/auth/facebook/disconnect')

                // Limpiar estado
                this.isConnected = false
                this.connectionData = null
                this.adAccounts = []
                this.pages = []
                this.selectedAdAccountId = null
                this.selectedPageId = null
                this.campaigns = [] // Reset campaigns

                localStorage.removeItem('fb_connected')

                return true
            } catch (error) {
                console.error('[FacebookStore] Disconnect error:', error)
                this.error = error.response?.data?.error || 'Error desconectando'
                throw error
            } finally {
                this.isLoading = false
            }
        },

        /**
         * Seleccionar y guardar activos (Ad Account / Page)
         */
        async saveAssetsSelection(adAccountId, pageId = null) {
            this.isLoading = true;
            try {
                await api.post('/auth/facebook/select-assets', {
                    ad_account_id: adAccountId,
                    page_id: pageId
                });

                this.selectedAdAccountId = adAccountId;
                this.selectedPageId = pageId;

                // Refresh local cache if needed
                if (this.connectionData) {
                    this.connectionData.selected_ad_account_id = adAccountId;
                    this.connectionData.selected_page_id = pageId;
                }

                return true;
            } catch (error) {
                console.error('[FacebookStore] Error saving selection:', error);
                this.error = error.response?.data?.error || 'Error guardando selección';
                throw error;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Obtener Campañas
         */
        async fetchCampaigns() {
            this.isLoading = true;
            this.error = null;
            try {
                const response = await api.get('/auth/facebook/campaigns');
                if (response.data.success) {
                    this.campaigns = response.data.data;
                    return this.campaigns;
                }
            } catch (error) {
                console.error('[FacebookStore] Error fetching campaigns:', error);
                this.error = error.response?.data?.error || 'Error obteniendo campañas';
                // Don't throw always, just set error state
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Iniciar flujo OAuth (abre popup/redirect)
         */
        async startOAuthFlow() {
            const loginUrl = await this.getLoginUrl()

            // Opción 1: Redirect (más simple para mobile)
            window.location.href = loginUrl
        },

        /**
         * Helper local setters (si no queremos guardar en server inmediatamente)
         */
        setSelectedAdAccount(accountId) {
            this.selectedAdAccountId = accountId
        },

        setSelectedPage(pageId) {
            this.selectedPageId = pageId
        },

        /**
         * Inicializar store (llamar al inicio de la app)
         */
        async init() {
            const wasConnected = localStorage.getItem('fb_connected') === 'true'
            if (wasConnected) {
                await this.checkConnectionStatus()
            }
        }
    },
})
