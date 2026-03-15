<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organizationStore'

const route = useRoute()
const router = useRouter()
const orgStore = useOrganizationStore()
const loading = ref(false)
const showAddNumberDialog = ref(false)
const activeTab = ref('overview')

const numberFormData = ref({
  phone_number: '',
  display_name: '',
  phone_number_id: '',
  waba_id: '',
  access_token: '',
  verify_token: '',
  webhook_url: ''
})

const organization = computed(() => 
  orgStore.organizations.find(o => o.id === Number(route.params.id))
)

const phoneNumbers = computed(() => 
  orgStore.phoneNumbers.filter(p => p.organization_id === Number(route.params.id))
)

onMounted(async () => {
  loading.value = true
  await Promise.all([
    orgStore.fetchOrganizations(),
    orgStore.fetchPhoneNumbers()
  ])
  loading.value = false
})

const addPhoneNumber = async () => {
  try {
    await orgStore.createPhoneNumber({
      ...numberFormData.value,
      organization_id: Number(route.params.id)
    })
    showAddNumberDialog.value = false
    resetNumberForm()
  } catch (error) {
    console.error('Error adding phone number:', error)
  }
}

const resetNumberForm = () => {
  numberFormData.value = {
    phone_number: '',
    display_name: '',
    phone_number_id: '',
    waba_id: '',
    access_token: '',
    verify_token: '',
    webhook_url: ''
  }
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    active: 'success',
    pending: 'warning',
    suspended: 'error',
    inactive: 'grey'
  }
  return colors[status] || 'grey'
}

const getQualityColor = (quality: string) => {
  const colors: Record<string, string> = {
    green: 'success',
    yellow: 'warning',
    red: 'error'
  }
  return colors[quality] || 'grey'
}
</script>

<template>
  <div v-if="loading" class="text-center pa-12">
    <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
  </div>

  <div v-else-if="!organization" class="text-center pa-12">
    <v-icon size="80" color="grey">mdi-alert-circle</v-icon>
    <h3 class="text-h5 mt-4">Organización no encontrada</h3>
    <v-btn color="primary" class="mt-4" @click="router.push('/organizations')">
      Volver a Organizaciones
    </v-btn>
  </div>

  <div v-else>
    <v-row class="mb-6">
      <v-col cols="12">
        <div class="d-flex align-center mb-4">
          <v-btn
            icon="mdi-arrow-left"
            variant="text"
            @click="router.push('/organizations')"
            class="mr-3"
          ></v-btn>
          <div class="flex-grow-1">
            <div class="d-flex align-center">
              <h1 class="text-h4 font-weight-bold mr-3">{{ organization.name }}</h1>
              <v-chip
                :color="organization.is_active ? 'success' : 'error'"
                size="small"
              >
                {{ organization.is_active ? 'Activa' : 'Inactiva' }}
              </v-chip>
            </div>
            <p class="text-subtitle-1 text-medium-emphasis mt-1">
              {{ organization.description || 'Sin descripción' }}
            </p>
          </div>
        </div>

        <v-card elevation="2">
          <v-tabs v-model="activeTab" bg-color="primary">
            <v-tab value="overview">
              <v-icon start>mdi-view-dashboard</v-icon>
              Resumen
            </v-tab>
            <v-tab value="numbers">
              <v-icon start>mdi-whatsapp</v-icon>
              Números WhatsApp
              <v-badge
                :content="phoneNumbers.length"
                color="success"
                inline
                class="ml-2"
              ></v-badge>
            </v-tab>
            <v-tab value="settings">
              <v-icon start>mdi-cog</v-icon>
              Configuración
            </v-tab>
          </v-tabs>

          <v-window v-model="activeTab">
            <v-window-item value="overview">
              <v-card-text>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-information</v-icon>
                        Información General
                      </v-card-title>
                      <v-card-text>
                        <v-list lines="two">
                          <v-list-item>
                            <template #prepend>
                              <v-icon>mdi-package-variant</v-icon>
                            </template>
                            <v-list-item-title>Plan</v-list-item-title>
                            <v-list-item-subtitle>
                              <v-chip :color="organization.plan === 'enterprise' ? 'success' : 'primary'" size="small">
                                {{ organization.plan.toUpperCase() }}
                              </v-chip>
                            </v-list-item-subtitle>
                          </v-list-item>

                          <v-list-item v-if="organization.email">
                            <template #prepend>
                              <v-icon>mdi-email</v-icon>
                            </template>
                            <v-list-item-title>Email</v-list-item-title>
                            <v-list-item-subtitle>{{ organization.email }}</v-list-item-subtitle>
                          </v-list-item>

                          <v-list-item v-if="organization.phone">
                            <template #prepend>
                              <v-icon>mdi-phone</v-icon>
                            </template>
                            <v-list-item-title>Teléfono</v-list-item-title>
                            <v-list-item-subtitle>{{ organization.phone }}</v-list-item-subtitle>
                          </v-list-item>

                          <v-list-item v-if="organization.website">
                            <template #prepend>
                              <v-icon>mdi-web</v-icon>
                            </template>
                            <v-list-item-title>Sitio Web</v-list-item-title>
                            <v-list-item-subtitle>
                              <a :href="organization.website" target="_blank">{{ organization.website }}</a>
                            </v-list-item-subtitle>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-chart-box</v-icon>
                        Estadísticas
                      </v-card-title>
                      <v-card-text>
                        <v-row>
                          <v-col cols="6">
                            <div class="text-center pa-4">
                              <v-icon size="48" color="success">mdi-whatsapp</v-icon>
                              <h3 class="text-h4 font-weight-bold mt-2">{{ phoneNumbers.length }}</h3>
                              <p class="text-caption text-medium-emphasis">Números WhatsApp</p>
                            </div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-center pa-4">
                              <v-icon size="48" color="primary">mdi-account-group</v-icon>
                              <h3 class="text-h4 font-weight-bold mt-2">{{ organization.users_count || 0 }}</h3>
                              <p class="text-caption text-medium-emphasis">Usuarios</p>
                            </div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-center pa-4">
                              <v-icon size="48" color="info">mdi-account-multiple</v-icon>
                              <h3 class="text-h4 font-weight-bold mt-2">0</h3>
                              <p class="text-caption text-medium-emphasis">Leads</p>
                            </div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-center pa-4">
                              <v-icon size="48" color="warning">mdi-message</v-icon>
                              <h3 class="text-h4 font-weight-bold mt-2">0</h3>
                              <p class="text-caption text-medium-emphasis">Conversaciones</p>
                            </div>
                          </v-col>
                        </v-row>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-window-item>

            <v-window-item value="numbers">
              <v-card-text>
                <div class="d-flex justify-space-between align-center mb-4">
                  <h3 class="text-h6">Números de WhatsApp</h3>
                  <v-btn
                    color="success"
                    prepend-icon="mdi-plus"
                    @click="showAddNumberDialog = true"
                  >
                    Agregar Número
                  </v-btn>
                </div>

                <v-row v-if="phoneNumbers.length === 0">
                  <v-col cols="12">
                    <v-card variant="outlined" class="text-center pa-8">
                      <v-icon size="64" color="grey-lighten-1">mdi-phone-off</v-icon>
                      <h4 class="text-h6 mt-4 mb-2">No hay números configurados</h4>
                      <p class="text-body-2 text-medium-emphasis mb-4">
                        Agrega tu primer número de WhatsApp para comenzar
                      </p>
                      <v-btn color="success" @click="showAddNumberDialog = true">
                        Agregar Primer Número
                      </v-btn>
                    </v-card>
                  </v-col>
                </v-row>

                <v-row v-else>
                  <v-col
                    v-for="number in phoneNumbers"
                    :key="number.id"
                    cols="12"
                    md="6"
                  >
                    <v-card elevation="2" hover>
                      <v-card-text>
                        <div class="d-flex justify-space-between align-center mb-3">
                          <v-chip
                            :color="getStatusColor(number.status)"
                            size="small"
                          >
                            {{ number.status }}
                          </v-chip>
                          <v-chip
                            v-if="number.quality_rating"
                            :color="getQualityColor(number.quality_rating)"
                            size="small"
                          >
                            {{ number.quality_rating }}
                          </v-chip>
                        </div>

                        <div class="d-flex align-center mb-3">
                          <v-avatar color="success" size="48" class="mr-3">
                            <v-icon size="32">mdi-whatsapp</v-icon>
                          </v-avatar>
                          <div>
                            <h4 class="text-h6">{{ number.display_name }}</h4>
                            <p class="text-body-2 text-medium-emphasis">{{ number.phone_number }}</p>
                          </div>
                        </div>

                        <v-divider class="my-3"></v-divider>

                        <v-list density="compact">
                          <v-list-item>
                            <template #prepend>
                              <v-icon size="20">mdi-identifier</v-icon>
                            </template>
                            <v-list-item-title class="text-caption">Phone Number ID</v-list-item-title>
                            <v-list-item-subtitle class="text-caption">{{ number.phone_number_id }}</v-list-item-subtitle>
                          </v-list-item>

                          <v-list-item>
                            <template #prepend>
                              <v-icon size="20">mdi-account-box</v-icon>
                            </template>
                            <v-list-item-title class="text-caption">WABA ID</v-list-item-title>
                            <v-list-item-subtitle class="text-caption">{{ number.waba_id }}</v-list-item-subtitle>
                          </v-list-item>

                          <v-list-item v-if="number.verified_at">
                            <template #prepend>
                              <v-icon size="20" color="success">mdi-check-circle</v-icon>
                            </template>
                            <v-list-item-title class="text-caption">Verificado</v-list-item-title>
                            <v-list-item-subtitle class="text-caption">
                              {{ new Date(number.verified_at).toLocaleDateString() }}
                            </v-list-item-subtitle>
                          </v-list-item>
                        </v-list>
                      </v-card-text>

                      <v-card-actions>
                        <v-btn variant="text" color="primary" size="small">
                          Editar
                        </v-btn>
                        <v-btn variant="text" color="error" size="small">
                          Eliminar
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-chip v-if="number.is_default" color="primary" size="small">
                          Predeterminado
                        </v-chip>
                      </v-card-actions>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-window-item>

            <v-window-item value="settings">
              <v-card-text>
                <v-alert type="info" variant="tonal" class="mb-4">
                  <v-alert-title>Configuración de la Organización</v-alert-title>
                  Próximamente podrás editar la información de la organización desde aquí.
                </v-alert>
              </v-card-text>
            </v-window-item>
          </v-window>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="showAddNumberDialog" max-width="700">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center bg-success">
          <span class="text-h5 text-white">
            <v-icon class="mr-2">mdi-whatsapp</v-icon>
            Agregar Número de WhatsApp
          </span>
          <v-btn icon="mdi-close" variant="text" color="white" @click="showAddNumberDialog = false"></v-btn>
        </v-card-title>

        <v-card-text class="pt-6">
          <v-form @submit.prevent="addPhoneNumber">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="numberFormData.phone_number"
                  label="Número de Teléfono"
                  prepend-inner-icon="mdi-phone"
                  variant="outlined"
                  placeholder="+584241234567"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="numberFormData.display_name"
                  label="Nombre para Mostrar"
                  prepend-inner-icon="mdi-label"
                  variant="outlined"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="numberFormData.phone_number_id"
                  label="Phone Number ID"
                  prepend-inner-icon="mdi-identifier"
                  variant="outlined"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="numberFormData.waba_id"
                  label="WABA ID"
                  prepend-inner-icon="mdi-account-box"
                  variant="outlined"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model="numberFormData.access_token"
                  label="Access Token"
                  prepend-inner-icon="mdi-key"
                  variant="outlined"
                  type="password"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="numberFormData.verify_token"
                  label="Verify Token"
                  prepend-inner-icon="mdi-shield-check"
                  variant="outlined"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="numberFormData.webhook_url"
                  label="Webhook URL"
                  prepend-inner-icon="mdi-webhook"
                  variant="outlined"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showAddNumberDialog = false">Cancelar</v-btn>
          <v-btn
            color="success"
            variant="flat"
            :loading="orgStore.loading"
            @click="addPhoneNumber"
          >
            <v-icon start>mdi-check</v-icon>
            Agregar Número
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped>
a {
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}
</style>
