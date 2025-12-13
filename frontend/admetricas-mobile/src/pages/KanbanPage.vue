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
            {{ getLeadsForStage(stage.value).length }}
          </q-badge>
        </div>

        <draggable
          :list="getLeadsForStage(stage.value)"
          group="leads"
          item-key="id"
          class="kanban-list"
          :clone="cloneLead"
          @start="onDragStart"
          @end="onDragEnd"
        >
          <template #item="{ element }">
            <q-card 
              class="lead-card q-mb-sm" 
              :class="{ 'dragging': draggedLeadId === element.id }"
              @click="viewLead(element.id)"
            >
              <q-card-section class="q-pa-sm">
                <div class="text-subtitle2 text-weight-bold">{{ element.client_name }}</div>
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
                  :value="Number(element.confidence_score) || 0"
                  :color="getConfidenceColor(Number(element.confidence_score) || 0)"
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
import { ref, onMounted } from 'vue';
import { useLeadStore } from 'stores/lead-store';
import { useRouter } from 'vue-router';
import draggable from 'vuedraggable';

const leadStore = useLeadStore();
const router = useRouter();

const draggedLeadId = ref<number | null>(null);
const draggedFromStage = ref<string | null>(null);

const stages = [
  { value: 'nuevo', label: 'Nuevo', color: '#9e9e9e' },
  { value: 'contactado', label: 'Contactado', color: '#ff9800' },
  { value: 'interesado', label: 'Interesado', color: '#2196f3' },
  { value: 'cliente', label: 'Cliente', color: '#4caf50' },
];

onMounted(async () => {
  await leadStore.fetchLeads();
});

// Get leads filtered by stage - returns a new array each time
const getLeadsForStage = (stageValue: string) => {
  return leadStore.leads.filter((lead: any) => lead.stage === stageValue);
};

// Clone function for vuedraggable
const cloneLead = (lead: any) => {
  return { ...lead };
};

const onDragStart = (evt: any) => {
  const lead = evt.item._underlying_vm_;
  draggedLeadId.value = lead.id;
  draggedFromStage.value = lead.stage;
  console.log(`[Kanban] Started dragging lead ${lead.id} from ${lead.stage}`);
};

const onDragEnd = async (evt: any) => {
  const lead = evt.item._underlying_vm_;
  const toColumn = evt.to.closest('.kanban-column');
  const toStageIndex = Array.from(toColumn.parentNode.children).indexOf(toColumn);
  const newStage = stages[toStageIndex]?.value;

  console.log(`[Kanban] Dropped lead ${lead.id}. New stage: ${newStage}, Old stage: ${draggedFromStage.value}`);

  if (newStage && newStage !== draggedFromStage.value) {
    // Update the lead's stage directly in the store
    const leadInStore = leadStore.leads.find((l: any) => l.id === lead.id);
    if (leadInStore) {
      leadInStore.stage = newStage;
      console.log(`[Kanban] Updated lead ${lead.id} stage to ${newStage}`);
      
      // Call API to persist
      await leadStore.updateLeadStage(lead.id, newStage);
    }
  }

  draggedLeadId.value = null;
  draggedFromStage.value = null;
};

const viewLead = (id: number) => {
  if (!draggedLeadId.value) {
    router.push(`/leads/${id}/conversations`);
  }
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
  transition: box-shadow 0.2s, opacity 0.2s;
}

.lead-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.lead-card:active {
  cursor: grabbing;
}

.lead-card.dragging {
  opacity: 0.5;
}
</style>
