<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const showCreateDialog = ref(false)
const formData = ref({
  name: '',
  description: '',
  email: '',
  phone: '',
  website: '',
  plan: 'free'
})

// Datos de ejemplo para modo demo
const organizations = ref([
  {
    id: 1,
    name: 'Ads Vzla',
    description: 'Agencia de marketing digital',
    email: 'ads@adsvzla.com',
    phone: '04242536795',
    plan: 'enterprise',
    is_active: true,
    phone_numbers_count: 2,
    users_count: 5
  },
  {
    id: 2,
    name: 'Tech Solutions',
    description: 'Soluciones tecnológicas',
    email: 'info@techsolutions.com',
    phone: '04141234567',
    plan: 'pro',
    is_active: true,
    phone_numbers_count: 1,
    users_count: 3
  },
  {
    id: 3,
    name: 'Marketing Pro',
    description: 'Expertos en marketing',
    email: 'contact@marketingpro.com',
    phone: '04167891234',
    plan: 'basic',
    is_active: true,
    phone_numbers_count: 1,
    users_count: 2
  }
])

const stats = computed(() => ({
  total: organizations.value.length,
  active: organizations.value.filter(o => o.is_active).length,
  enterprise: organizations.value.filter(o => o.plan === 'enterprise').length,
  totalNumbers: organizations.value.reduce((sum, o) => sum + (o.phone_numbers_count || 0), 0)
}))

const getPlanColor = (plan: string) => {
  const colors: Record<string, string> = {
    free: 'grey',
    basic: 'info',
    pro: 'purple',
    enterprise: 'success'
  }
  return colors[plan] || 'grey'
}

const viewOrganization = (id: number) => {
  router.push(`/dashboard/organizations/${id}`)
}

const createOrganization = () => {
  // Demo: agregar organización localmente
  const newOrg = {
    id: organizations.value.length + 1,
    ...formData.value,
    is_active: true,
    phone_numbers_count: 0,
    users_count: 1
  }
  organizations.value.push(newOrg)
  showCreateDialog.value = false
  formData.value = { name: '', description: '', email: '', phone: '', website: '', plan: 'free' }
}
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Organizaciones</h1>
        <p class="text-body-2 text-medium-emphasis">Gestiona tus organizaciones y números de WhatsApp</p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="showCreateDialog = true">
        Nueva Organización
      </v-btn>
    </div>

    <!-- Stats Cards -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" lg="3">
        <v-card class="stat-card" elevation="0">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-medium-emphasis mb-1">Total Organizaciones</p>
                <h2 class="text-h4 font-weight-bold">{{ stats.total }}</h2>
                <p class="text-caption text-medium-emphasis mt-1">
                  Registradas en sistema
                </p>
              </div>
              <div class="stat-icon stat-icon-primary">
                <v-icon size="28">mdi-domain</v-icon>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card class="stat-card" elevation="0">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-medium-emphasis mb-1">Activas</p>
                <h2 class="text-h4 font-weight-bold">{{ stats.active }}</h2>
                <p class="text-caption text-success font-weight-medium mt-1">
                  <v-icon size="small" color="success" class="mr-1">mdi-trending-up</v-icon>
                  {{ Math.round((stats.active / stats.total) * 100) || 0 }}% del total
                </p>
              </div>
              <div class="stat-icon stat-icon-success">
                <v-icon size="28">mdi-check-circle-outline</v-icon>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card class="stat-card" elevation="0">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-medium-emphasis mb-1">Enterprise</p>
                <h2 class="text-h4 font-weight-bold">{{ stats.enterprise }}</h2>
                <p class="text-caption text-medium-emphasis mt-1">
                  Plan premium
                </p>
              </div>
              <div class="stat-icon stat-icon-warning">
                <v-icon size="28">mdi-crown-outline</v-icon>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="3">
        <v-card class="stat-card" elevation="0">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-medium-emphasis mb-1">WhatsApp</p>
                <h2 class="text-h4 font-weight-bold">{{ stats.totalNumbers }}</h2>
                <p class="text-caption text-medium-emphasis mt-1">
                  Números conectados
                </p>
              </div>
              <div class="stat-icon stat-icon-info">
                <v-icon size="28">mdi-whatsapp</v-icon>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Organizations Grid -->
    <v-row v-if="!loading && organizations.length > 0">
      <v-col
        v-for="org in organizations"
        :key="org.id"
        cols="12"
        md="6"
        lg="4"
      >
        <v-card
          class="org-card h-100"
          elevation="0"
          @click="viewOrganization(org.id)"
        >
          <v-card-text class="pa-5">
            <!-- Header -->
            <div class="d-flex align-center mb-4">
              <v-avatar
                size="56"
                :color="getPlanColor(org.plan)"
                class="mr-4"
              >
                <span class="text-h5 font-weight-bold text-white">
                  {{ org.name.charAt(0).toUpperCase() }}
                </span>
              </v-avatar>
              <div class="flex-grow-1">
                <h3 class="text-h6 font-weight-bold mb-1">{{ org.name }}</h3>
                <v-chip
                  :color="getPlanColor(org.plan)"
                  size="x-small"
                  variant="flat"
                  class="text-uppercase font-weight-bold"
                >
                  {{ org.plan }}
                </v-chip>
              </div>
              <v-chip
                :color="org.is_active ? 'success' : 'grey'"
                size="small"
                variant="tonal"
              >
                <v-icon start size="12">mdi-circle</v-icon>
                {{ org.is_active ? 'Activa' : 'Inactiva' }}
              </v-chip>
            </div>

            <!-- Description -->
            <p class="text-body-2 text-medium-emphasis mb-4" style="min-height: 44px; line-height: 1.5;">
              {{ org.description || 'Sin descripción disponible' }}
            </p>

            <!-- Stats -->
            <div class="org-stats d-flex gap-4 mb-4">
              <div class="org-stat">
                <v-icon size="20" color="success" class="mb-1">mdi-whatsapp</v-icon>
                <p class="text-h6 font-weight-bold mb-0">{{ org.phone_numbers_count || 0 }}</p>
                <p class="text-caption text-medium-emphasis">Números</p>
              </div>
              <div class="org-stat">
                <v-icon size="20" color="primary" class="mb-1">mdi-account-group</v-icon>
                <p class="text-h6 font-weight-bold mb-0">{{ org.users_count || 0 }}</p>
                <p class="text-caption text-medium-emphasis">Usuarios</p>
              </div>
              <div class="org-stat">
                <v-icon size="20" color="orange" class="mb-1">mdi-message</v-icon>
                <p class="text-h6 font-weight-bold mb-0">0</p>
                <p class="text-caption text-medium-emphasis">Mensajes</p>
              </div>
            </div>

            <!-- Contact Info -->
            <v-divider class="mb-3"></v-divider>
            <div class="d-flex flex-wrap gap-3">
              <div v-if="org.email" class="d-flex align-center">
                <v-icon size="16" color="grey" class="mr-1">mdi-email-outline</v-icon>
                <span class="text-caption">{{ org.email }}</span>
              </div>
              <div v-if="org.phone" class="d-flex align-center">
                <v-icon size="16" color="grey" class="mr-1">mdi-phone-outline</v-icon>
                <span class="text-caption">{{ org.phone }}</span>
              </div>
            </div>
          </v-card-text>

          <v-card-actions class="px-5 pb-4">
            <v-btn
              variant="tonal"
              color="primary"
              rounded="lg"
              prepend-icon="mdi-eye"
              @click.stop="viewOrganization(org.id)"
            >
              Ver Detalles
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn icon variant="text" size="small">
              <v-icon>mdi-pencil-outline</v-icon>
            </v-btn>
            <v-btn icon variant="text" size="small" color="error">
              <v-icon>mdi-delete-outline</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <!-- Empty State -->
    <v-card v-else-if="!loading && organizations.length === 0" class="empty-state text-center pa-12" elevation="0">
      <v-icon size="80" color="grey-lighten-1" class="mb-4">mdi-domain-plus</v-icon>
      <h3 class="text-h5 font-weight-bold mb-2">No hay organizaciones</h3>
      <p class="text-body-1 text-medium-emphasis mb-6">
        Comienza creando tu primera organización para gestionar tus números de WhatsApp
      </p>
      <v-btn
        color="primary"
        size="large"
        rounded="lg"
        prepend-icon="mdi-plus"
        @click="showCreateDialog = true"
      >
        Crear Primera Organización
      </v-btn>
    </v-card>

    <v-dialog v-model="showCreateDialog" max-width="600">
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h5">Nueva Organización</span>
          <v-btn icon="mdi-close" variant="text" @click="showCreateDialog = false"></v-btn>
        </v-card-title>

        <v-card-text>
          <v-form @submit.prevent="createOrganization">
            <v-text-field
              v-model="formData.name"
              label="Nombre de la Organización"
              prepend-inner-icon="mdi-domain"
              variant="outlined"
              required
              class="mb-3"
            ></v-text-field>

            <v-textarea
              v-model="formData.description"
              label="Descripción"
              prepend-inner-icon="mdi-text"
              variant="outlined"
              rows="3"
              class="mb-3"
            ></v-textarea>

            <v-select
              v-model="formData.plan"
              label="Plan"
              prepend-inner-icon="mdi-package-variant"
              variant="outlined"
              :items="[
                { title: 'Free', value: 'free' },
                { title: 'Basic', value: 'basic' },
                { title: 'Pro', value: 'pro' },
                { title: 'Enterprise', value: 'enterprise' }
              ]"
              class="mb-3"
            ></v-select>

            <v-text-field
              v-model="formData.email"
              label="Email"
              prepend-inner-icon="mdi-email"
              variant="outlined"
              type="email"
              class="mb-3"
            ></v-text-field>

            <v-text-field
              v-model="formData.phone"
              label="Teléfono"
              prepend-inner-icon="mdi-phone"
              variant="outlined"
              class="mb-3"
            ></v-text-field>

            <v-text-field
              v-model="formData.website"
              label="Sitio Web"
              prepend-inner-icon="mdi-web"
              variant="outlined"
            ></v-text-field>
          </v-form>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showCreateDialog = false">Cancelar</v-btn>
          <v-btn
            color="primary"
            variant="flat"
            :loading="loading"
            @click="createOrganization"
          >
            Crear Organización
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped>
.organizations-page {
  max-width: 1400px;
  margin: 0 auto;
}

.stat-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  background: white;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon-primary {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1976d2;
}

.stat-icon-success {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #388e3c;
}

.stat-icon-warning {
  background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
  color: #f57c00;
}

.stat-icon-info {
  background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
  color: #00897b;
}

.org-card {
  border-radius: 20px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  background: white;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.org-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  border-color: rgba(25, 118, 210, 0.3);
}

.org-stats {
  background: #f8fafc;
  border-radius: 12px;
  padding: 16px;
}

.org-stat {
  text-align: center;
  flex: 1;
}

.empty-state {
  border-radius: 20px;
  background: white;
  border: 2px dashed #e0e0e0;
}

.gap-3 {
  gap: 12px;
}

.gap-4 {
  gap: 16px;
}
</style>
