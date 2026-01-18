import { defineStore } from 'pinia'
import { api } from 'boot/axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: JSON.parse(localStorage.getItem('user') || 'null'),
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(email, password) {
            console.log('[AuthStore] Attempting login for:', email)
            try {
                const response = await api.post('/login', { email, password })
                console.log('[AuthStore] Login success:', response.data)
                this.token = response.data.token
                this.user = response.data.user

                localStorage.setItem('token', this.token)
                localStorage.setItem('user', JSON.stringify(this.user))

                // Set default header
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

                return true
            } catch (error) {
                console.error('[AuthStore] Login failed:', error.response?.data || error.message)
                throw error
            }
        },
        logout() {
            this.token = null
            this.user = null
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            delete api.defaults.headers.common['Authorization']
        },
        init() {
            if (this.token) {
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
            }
        }
    },
})
