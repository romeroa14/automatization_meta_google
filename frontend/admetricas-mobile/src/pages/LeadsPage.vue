<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">CRM Leads</div>

    <q-table
      title="Leads"
      :rows="leadStore.leads"
      :columns="columns"
      row-key="id"
      :loading="leadStore.loading"
      v-model:pagination="pagination"
      @request="onRequest"
    >
      <template v-slot:body-cell-intent="props">
        <q-td :props="props">
          <q-badge :color="getIntentColor(props.value)">
            {{ props.value }}
          </q-badge>
        </q-td>
      </template>

       <template v-slot:body-cell-stage="props">
        <q-td :props="props">
          <q-badge :color="getStageColor(props.value)">
            {{ props.value }}
          </q-badge>
        </q-td>
      </template>

      <template v-slot:body-cell-confidence_score="props">
        <q-td :props="props">
          <q-linear-progress size="15px" :value="Number(props.value)" :color="getConfidenceColor(Number(props.value))">
            <div class="absolute-full flex flex-center">
              <q-badge color="transparent" text-color="white" :label="(Number(props.value) * 100).toFixed(0) + '%'" />
            </div>
          </q-linear-progress>
        </q-td>
      </template>
      
      <template v-slot:body-cell-actions="props">
          <q-td :props="props">
              <q-btn flat round color="primary" icon="chat" @click="viewConversations(props.row.id)" />
          </q-td>
      </template>
    </q-table>
  </q-page>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useLeadStore } from 'stores/lead-store';
import { useRouter } from 'vue-router';
import { QTableColumn } from 'quasar';

const leadStore = useLeadStore();
const router = useRouter();

const pagination = ref({
    sortBy: 'desc',
    descending: false,
    page: 1,
    rowsPerPage: 20,
    rowsNumber: 10
});

const columns: QTableColumn[] = [
  { name: 'client_name', label: 'Nombre', field: 'client_name', align: 'left' },
  { name: 'phone_number', label: 'Teléfono', field: 'phone_number', align: 'left' },
  { name: 'intent', label: 'Intención', field: 'intent', align: 'left' },
  { name: 'stage', label: 'Etapa', field: 'stage', align: 'left' },
  { name: 'confidence_score', label: 'Confianza', field: 'confidence_score', align: 'center' },
  { name: 'actions', label: 'Acciones', field: 'id', align: 'center' },
];

onMounted(() => {
  leadStore.fetchLeads();
});

const onRequest = (props: any) => {
    const { page, rowsPerPage } = props.pagination;
    leadStore.fetchLeads(page);
    pagination.value.page = page;
    pagination.value.rowsPerPage = rowsPerPage;
};

const getIntentColor = (intent: string) => {
  const map: Record<string, string> = { compra: 'green', consulta: 'blue', reclamo: 'red', pricing: 'green', info: 'blue' };
  return map[intent] || 'grey';
};

const getStageColor = (stage: string) => {
    const map: Record<string, string> = { 
        nuevo: 'grey', 
        contactado: 'orange', 
        interesado: 'blue', 
        cliente: 'green',
        pricing_discussion: 'orange',
        ready_to_buy: 'green'
    };
    return map[stage] || 'grey';
};

const getConfidenceColor = (score: number) => {
  if (score >= 0.8) return 'green';
  if (score >= 0.5) return 'orange';
  return 'red';
};

const viewConversations = (id: string) => {
    router.push(`/leads/${id}/conversations`);
};
</script>
