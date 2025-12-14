<template>
  <q-page class="q-pa-md kanban-page">
    <div class="text-h5 q-mb-md">Tablero Kanban - Leads</div>

    <div class="kanban-container">
      <div
        v-for="stage in stages"
        :key="stage.value"
        class="kanban-column"
        :style="{ borderTopColor: stage.color }"
      >
        <div class="column-header" :style="{ backgroundColor: stage.color }">
          <span class="text-white text-weight-bold">{{ stage.label }}</span>
          <q-badge color="white" :text-color="stage.color" class="q-ml-sm">
            {{ getLeadsByStage(stage.value).length }}
          </q-badge>
        </div>

        <draggable
          v-model="leadsByStage[stage.value]"
          group="leads"
          item-key="id"
          class="kanban-list"
          @change="(evt) => onDragChange(evt, stage.value)"
        >
          <template #item="{ element }">
            <q-card class="lead-card q-mb-sm" @click="viewLead(element.id)">
              <q-card-section class="q-pa-sm">
                <div class="row items-center justify-between">
                     <div class="text-subtitle2 text-weight-bold">{{ element.client_name }}</div>
                     <div>
                         <q-icon v-if="Number(element.confidence_score) >= 0.8" name="local_fire_department" color="orange" size="xs">
                             <q-tooltip>Hot Lead!</q-tooltip>
                         </q-icon>
                         <q-icon v-if="element.intent === 'reclamo'" name="warning" color="red" size="xs">
                             <q-tooltip>Requiere Atención</q-tooltip>
                         </q-icon>
                     </div>
                </div>
                <div class="text-caption text-grey-7">
                  <q-icon name="phone" size="xs" /> {{ element.phone_number }}
                </div>
                <div class="q-mt-xs">
                  <q-badge :color="getIntentColor(element.intent)" size="sm">
                    {{ element.intent }}
                  </q-badge>
                </div>
                <q-linear-progress
                  class="q-mt-sm"
                  size="8px"
                  :value="Number(element.confidence_score)"
                  :color="getConfidenceColor(Number(element.confidence_score))"
                />
              </q-card-section>
            </q-card>
          </template>
        </draggable>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useLeadStore } from 'stores/lead-store';
import { useRouter } from 'vue-router';
import draggable from 'vuedraggable';

const leadStore = useLeadStore();
const router = useRouter();

const stages = [
  { value: 'nuevo', label: 'Nuevo', color: '#9e9e9e' },
  { value: 'contactado', label: 'Contactado', color: '#ff9800' },
  { value: 'interesado', label: 'Interesado', color: '#2196f3' },
  { value: 'cliente', label: 'Cliente', color: '#4caf50' },
];

// Reactive object to hold leads grouped by stage
const leadsByStage = ref<Record<string, any[]>>({});

// Initialize leads grouped by stage, sorted by confidence score (Smart Sort)
const initializeLeadsByStage = () => {
  const grouped: Record<string, any[]> = {};
  stages.forEach((stage) => {
    grouped[stage.value] = leadStore.leads
      .filter((lead: any) => lead.stage === stage.value)
      .sort((a: any, b: any) => parseFloat(b.confidence_score || 0) - parseFloat(a.confidence_score || 0));
  });
  leadsByStage.value = grouped;
};

const getLeadsByStage = (stageValue: string) => {
  return leadsByStage.value[stageValue] || [];
};

// Watch for changes in the store
watch(() => leadStore.leads, initializeLeadsByStage, { deep: true });

onMounted(async () => {
  await leadStore.fetchLeads();
  initializeLeadsByStage();
});

const onDragChange = async (evt: any, newStage: string) => {
  if (evt.added) {
    const lead = evt.added.element;
    console.log(`[Kanban] Moving lead ${lead.id} to stage: ${newStage}`);
    
    // Update the lead's stage in the store immediately (optimistic update)
    const leadInStore = leadStore.leads.find((l: any) => l.id === lead.id);
    if (leadInStore) {
      leadInStore.stage = newStage;
    }
    
    // Call API in background
    await leadStore.updateLeadStage(lead.id, newStage);
  }
};

const viewLead = (id: number) => {
  router.push(`/leads/${id}/conversations`);
};

const getIntentColor = (intent: string) => {
  const map: Record<string, string> = {
    compra: 'green',
    consulta: 'blue',
    reclamo: 'red',
    pricing: 'green',
    info: 'blue',
  };
  return map[intent?.toLowerCase()] || 'grey';
};

const getConfidenceColor = (score: number) => {
  if (score >= 0.8) return 'green';
  if (score >= 0.5) return 'orange';
  return 'red';
};
</script>

<style scoped>
.kanban-page {
  background-color: #f5f5f5;
}

.kanban-container {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 16px;
}

.kanban-column {
  min-width: 280px;
  max-width: 300px;
  background-color: #e0e0e0;
  border-radius: 8px;
  border-top: 4px solid;
  flex-shrink: 0;
}

.column-header {
  padding: 12px;
  border-radius: 4px 4px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.kanban-list {
  padding: 12px;
  min-height: 400px;
}

.lead-card {
  cursor: grab;
  transition: box-shadow 0.2s;
}

.lead-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.lead-card:active {
  cursor: grabbing;
}
</style>
