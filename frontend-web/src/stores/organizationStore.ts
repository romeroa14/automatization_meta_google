import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/plugins/axios'

export interface Organization {
  id: number
  name: string
  slug: string
  description?: string
  logo_url?: string
  website?: string
  email?: string
  phone?: string
  settings?: Record<string, any>
  is_active: boolean
  plan: 'free' | 'basic' | 'pro' | 'enterprise'
  trial_ends_at?: string
  created_at: string
  updated_at: string
  phone_numbers_count?: number
  users_count?: number
  user_role?: 'owner' | 'admin' | 'member'
}

export interface WhatsAppPhoneNumber {
  id: number
  organization_id: number
  phone_number: string
  formatted_phone_number: string
  display_name?: string
  phone_number_id: string
  waba_id: string
  webhook_url?: string
  status: 'pending' | 'active' | 'suspended' | 'inactive'
  quality_rating?: 'green' | 'yellow' | 'red'
  capabilities?: Record<string, any>
  settings?: Record<string, any>
  verified_at?: string
  last_used_at?: string
  is_default: boolean
  created_at: string
  updated_at: string
  leads_count?: number
  conversations_count?: number
}

export const useOrganizationStore = defineStore('organization', () => {
  const organizations = ref<Organization[]>([])
  const currentOrganization = ref<Organization | null>(null)
  const phoneNumbers = ref<WhatsAppPhoneNumber[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Fetch all organizations for the user
  async function fetchOrganizations() {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get('/organizations')
      organizations.value = response.data.data || response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar organizaciones'
      console.error('Error fetching organizations:', err)
    } finally {
      loading.value = false
    }
  }

  // Fetch single organization
  async function fetchOrganization(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get(`/organizations/${id}`)
      currentOrganization.value = response.data.data || response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar organización'
      console.error('Error fetching organization:', err)
    } finally {
      loading.value = false
    }
  }

  // Create organization
  async function createOrganization(data: Partial<Organization>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post('/organizations', data)
      const newOrg = response.data.data || response.data
      organizations.value.push(newOrg)
      return newOrg
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al crear organización'
      console.error('Error creating organization:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update organization
  async function updateOrganization(id: number, data: Partial<Organization>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put(`/organizations/${id}`, data)
      const updatedOrg = response.data.data || response.data
      
      const index = organizations.value.findIndex(org => org.id === id)
      if (index !== -1) {
        organizations.value[index] = updatedOrg
      }
      
      if (currentOrganization.value?.id === id) {
        currentOrganization.value = updatedOrg
      }
      
      return updatedOrg
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al actualizar organización'
      console.error('Error updating organization:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete organization
  async function deleteOrganization(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/organizations/${id}`)
      organizations.value = organizations.value.filter(org => org.id !== id)
      if (currentOrganization.value?.id === id) {
        currentOrganization.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al eliminar organización'
      console.error('Error deleting organization:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Fetch phone numbers for an organization
  async function fetchPhoneNumbers(organizationId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get(`/organizations/${organizationId}/phone-numbers`)
      phoneNumbers.value = response.data.data || response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al cargar números'
      console.error('Error fetching phone numbers:', err)
    } finally {
      loading.value = false
    }
  }

  // Create phone number
  async function createPhoneNumber(organizationId: number, data: Partial<WhatsAppPhoneNumber>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post(`/organizations/${organizationId}/phone-numbers`, data)
      const newNumber = response.data.data || response.data
      phoneNumbers.value.push(newNumber)
      return newNumber
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al crear número'
      console.error('Error creating phone number:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update phone number
  async function updatePhoneNumber(organizationId: number, phoneNumberId: number, data: Partial<WhatsAppPhoneNumber>) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.put(`/organizations/${organizationId}/phone-numbers/${phoneNumberId}`, data)
      const updatedNumber = response.data.data || response.data
      
      const index = phoneNumbers.value.findIndex(num => num.id === phoneNumberId)
      if (index !== -1) {
        phoneNumbers.value[index] = updatedNumber
      }
      
      return updatedNumber
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al actualizar número'
      console.error('Error updating phone number:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Set as default phone number
  async function setDefaultPhoneNumber(organizationId: number, phoneNumberId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.post(`/organizations/${organizationId}/phone-numbers/${phoneNumberId}/set-default`)
      
      // Update local state
      phoneNumbers.value = phoneNumbers.value.map(num => ({
        ...num,
        is_default: num.id === phoneNumberId
      }))
      
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al establecer número predeterminado'
      console.error('Error setting default phone number:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Delete phone number
  async function deletePhoneNumber(organizationId: number, phoneNumberId: number) {
    loading.value = true
    error.value = null
    try {
      await apiClient.delete(`/organizations/${organizationId}/phone-numbers/${phoneNumberId}`)
      phoneNumbers.value = phoneNumbers.value.filter(num => num.id !== phoneNumberId)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Error al eliminar número'
      console.error('Error deleting phone number:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    organizations,
    currentOrganization,
    phoneNumbers,
    loading,
    error,
    fetchOrganizations,
    fetchOrganization,
    createOrganization,
    updateOrganization,
    deleteOrganization,
    fetchPhoneNumbers,
    createPhoneNumber,
    updatePhoneNumber,
    setDefaultPhoneNumber,
    deletePhoneNumber
  }
})
