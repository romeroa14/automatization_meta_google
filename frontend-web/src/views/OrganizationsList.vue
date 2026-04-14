<template>
  <v-app>
    <v-app-bar color="primary" prominent>
      <v-toolbar-title>Organizaciones</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="showCreateDialog = true">
        <v-icon>mdi-plus</v-icon>
      </v-btn>
    </v-app-bar>

    <v-main>
      <v-container>
        <!-- Loading State -->
        <v-row v-if="organizationStore.loading" justify="center" class="mt-8">
          <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
        </v-row>

        <!-- Organizations Grid -->
        <v-row v-else>
          <v-col
            v-for="org in organizationStore.organizations"
            :key="org.id"
            cols="12"
            md="6"
            lg="4"
          >
            <v-card elevation="2" @click="viewOrganization(org)">
              <v-card-title class="d-flex align-center">
                <v-avatar color="primary" class="mr-3">
                  <span class="text-h6">{{ org.name.charAt(0) }}</span>
                </v-avatar>
                {{ org.name }}
              </v-card-title>
              
              <v-card-subtitle v-if="org.description">
                {{ org.description }}
              </v-card-subtitle>

              <v-card-text>
                <v-chip :color="getPlanColor(org.plan)" size="small" class="mr-2">
                  {{ org.plan.toUpperCase() }}
                </v-chip>
                <v-chip v-if="org.is_active" color="success" size="small">
                  Activo
                </v-chip>
                <v-chip v-else color="error" size="small">
                  Inactivo
                </v-chip>

                <v-divider class="my-3"></v-divider>

                <div class="d-flex justify-space-between">
                  <div>
                    <v-icon size="small">mdi-phone</v-icon>
                    {{ org.phone_numbers_count || 0 }} números
                  </div>
                  <div>
                    <v-icon size="small">mdi-account-multiple</v-icon>
                    {{ org.users_count || 0 }} usuarios
                  </div>
                </div>

                <div v-if="org.user_role" class="mt-2">
                  <v-chip size="x-small" :color="getRoleColor(org.user_role)">
                    {{ org.user_role }}
                  </v-chip>
                </div>
              </v-card-text>

              <v-card-actions>
                <v-btn text @click.stop="editOrganization(org)">
                  <v-icon left>mdi-pencil</v-icon>
                  Editar
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn icon @click.stop="viewPhoneNumbers(org)">
                  <v-icon>mdi-chevron-right</v-icon>
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <!-- Empty State -->
        <v-row v-if="!organizationStore.loading && organizationStore.organizations.length === 0" justify="center" class="mt-8">
          <v-col cols="12" class="text-center">
            <v-icon size="64" color="grey">mdi-office-building</v-icon>
            <div class="text-h6 mt-4">No tienes organizaciones</div>
            <div class="text-caption">Crea tu primera organización para comenzar</div>
            <v-btn color="primary" class="mt-4" @click="showCreateDialog = true">
              Crear Organización
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="showCreateDialog" max-width="600">
      <v-card>
        <v-card-title>
          {{ editingOrg ? 'Editar Organización' : 'Nueva Organización' }}
        </v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-text-field
              v-model="formData.name"
              label="Nombre"
              required
              :rules="[v => !!v || 'El nombre es requerido']"
            ></v-text-field>

            <v-textarea
              v-model="formData.description"
              label="Descripción"
              rows="3"
            ></v-textarea>

            <v-text-field
              v-model="formData.email"
              label="Email"
              type="email"
            ></v-text-field>

            <v-text-field
              v-model="formData.phone"
              label="Teléfono"
            ></v-text-field>

            <v-text-field
              v-model="formData.website"
              label="Sitio Web"
              type="url"
            ></v-text-field>

            <v-select
              v-model="formData.plan"
              label="Plan"
              :items="['free', 'basic', 'pro', 'enterprise']"
              required
            ></v-select>

            <v-switch
              v-model="formData.is_active"
              label="Activo"
              color="success"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn text @click="closeDialog">Cancelar</v-btn>
          <v-btn color="primary" @click="saveOrganization" :loading="organizationStore.loading">
            Guardar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore, type Organization } from '@/stores/organizationStore'

const router = useRouter()
const organizationStore = useOrganizationStore()

const showCreateDialog = ref(false)
const editingOrg = ref<Organization | null>(null)
const formData = ref<{
  name: string
  description: string
  email: string
  phone: string
  website: string
  plan: 'free' | 'basic' | 'pro' | 'enterprise'
  is_active: boolean
}>({
  name: '',
  description: '',
  email: '',
  phone: '',
  website: '',
  plan: 'free',
  is_active: true
})

const getPlanColor = (plan: string) => {
  const colors: Record<string, string> = {
    free: 'grey',
    basic: 'orange',
    pro: 'green',
    enterprise: 'purple'
  }
  return colors[plan] || 'grey'
}

const getRoleColor = (role: string) => {
  const colors: Record<string, string> = {
    owner: 'purple',
    admin: 'blue',
    member: 'grey'
  }
  return colors[role] || 'grey'
}

const viewOrganization = (org: Organization) => {
  router.push(`/organizations/${org.id}`)
}

const editOrganization = (org: Organization) => {
  editingOrg.value = org
  formData.value = {
    name: org.name,
    description: org.description || '',
    email: org.email || '',
    phone: org.phone || '',
    website: org.website || '',
    plan: org.plan,
    is_active: org.is_active
  }
  showCreateDialog.value = true
}

const viewPhoneNumbers = (org: Organization) => {
  router.push(`/organizations/${org.id}/phone-numbers`)
}

const closeDialog = () => {
  showCreateDialog.value = false
  editingOrg.value = null
  formData.value = {
    name: '',
    description: '',
    email: '',
    phone: '',
    website: '',
    plan: 'free',
    is_active: true
  }
}

const saveOrganization = async () => {
  try {
    if (editingOrg.value) {
      await organizationStore.updateOrganization(editingOrg.value.id, formData.value)
    } else {
      await organizationStore.createOrganization(formData.value)
    }
    closeDialog()
  } catch (error) {
    console.error('Error saving organization:', error)
  }
}

onMounted(async () => {
  await organizationStore.fetchOrganizations()
})
</script>

<style scoped>
.v-card {
  cursor: pointer;
  transition: transform 0.2s;
}

.v-card:hover {
  transform: translateY(-4px);
}
</style>
