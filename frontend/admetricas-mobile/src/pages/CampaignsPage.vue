<template>
  <q-page class="q-pa-md">
    <div v-if="!facebookStore.isConnected" class="flex flex-center column q-pa-xl">
       <q-icon name="lock" size="64px" color="grey" />
       <div class="text-h6 q-mt-md text-grey">Debes conectar tu cuenta de Facebook</div>
       <q-btn to="/" color="primary" label="Ir a Inicio" class="q-mt-md" />
    </div>

    <div v-else>
        <!-- Selector de Cuenta Publicitaria (si no ha seleccionado) -->
        <div v-if="!facebookStore.selectedAdAccountId" class="q-mb-xl">
             <div class="text-h5 q-mb-md">Selecciona una Cuenta Publicitaria</div>
             <q-option-group
                v-if="facebookStore.adAccounts.length > 0"
                v-model="selectedAdAccount"
                :options="adAccountOptions"
                color="primary"
             />
             <div v-else class="text-grey">No se encontraron cuentas publicitarias.</div>
             
             <q-btn 
                label="Guardar Selección" 
                color="primary" 
                class="q-mt-md" 
                :disable="!selectedAdAccount"
                :loading="facebookStore.isLoading"
                @click="saveSelection"
             />
        </div>

        <!-- Lista de Campañas -->
        <div v-else>
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Campañas Activas</div>
                <q-btn flat round icon="settings" @click="resetSelection" color="grey" title="Cambiar cuenta" />
            </div>

            <div v-if="facebookStore.campaigns.length === 0 && !facebookStore.isLoading" class="text-grey q-pa-md text-center">
                No hay campañas activas o visibles para esta cuenta.
            </div>

            <div class="row q-col-gutter-md">
              <div v-for="campaign in facebookStore.campaigns" :key="campaign.id" class="col-12 col-md-4">
                <q-card>
                  <q-card-section>
                    <div class="text-h6 ellipsis">{{ campaign.name }}</div>
                    <div class="text-caption">
                         <q-badge :color="campaign.status === 'ACTIVE' ? 'positive' : 'grey'">
                             {{ campaign.status }}
                         </q-badge>
                    </div>
                  </q-card-section>

                  <q-separator />

                  <q-card-section>
                    <div class="row items-center justify-between q-mb-sm">
                      <div>Gasto Total:</div>
                      <div class="text-weight-bold text-primary">${{ Number(campaign.amount_spent || 0).toFixed(2) }}</div>
                    </div>
                    <div class="row items-center justify-between q-mb-sm">
                       <div>Presupuesto Diario:</div>
                       <div>${{ campaign.daily_budget ? campaign.daily_budget.toFixed(2) : 'N/A' }}</div>
                    </div>
                    <div class="row items-center justify-between text-caption text-grey-8">
                        <div>Impresiones: {{ campaign.impressions }}</div>
                        <div>Clicks: {{ campaign.clicks }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
            
            <div v-if="facebookStore.isLoading" class="flex flex-center q-pa-lg">
                <q-spinner color="primary" size="3em" />
            </div>
        </div>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useFacebookStore } from 'stores/facebook-store';
import { useQuasar } from 'quasar';

const $q = useQuasar();
const facebookStore = useFacebookStore();
const selectedAdAccount = ref(null);

const adAccountOptions = computed(() => {
    return facebookStore.adAccounts.map(acc => ({
        label: `${acc.name} (${acc.account_id})`,
        value: acc.id
    }));
});

onMounted(async () => {
  await facebookStore.init();
  
  if (facebookStore.isConnected) {
    if (facebookStore.selectedAdAccountId) {
        await facebookStore.fetchCampaigns();
    }
  }
});

async function saveSelection() {
    if (!selectedAdAccount.value) return;
    try {
        await facebookStore.saveAssetsSelection(selectedAdAccount.value);
        await facebookStore.fetchCampaigns();
    } catch (error) {
        $q.notify({
            type: 'negative',
            message: 'Error guardando selección'
        });
    }
}

function resetSelection() {
    facebookStore.selectedAdAccountId = null;
    selectedAdAccount.value = null;
}
</script>
