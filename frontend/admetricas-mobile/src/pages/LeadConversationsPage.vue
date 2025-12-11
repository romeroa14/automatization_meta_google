<template>
  <q-page class="q-pa-md flex flex-col items-center">
    <div v-if="leadStore.currentLead" class="text-h6 q-mb-md w-full">
      Chat con {{ leadStore.currentLead.client_name }}
    </div>

    <div class="q-pa-md row justify-center w-full" style="width: 100%; max-width: 600px">
      <div style="width: 100%;">
        <q-chat-message
          v-for="conv in leadStore.conversations"
          :key="conv.id"
          :text="[conv.message_text]"
          :sent="!conv.is_client_message"
          :name="conv.is_client_message ? 'Cliente' : 'Sistema'"
          :stamp="formatDate(conv.timestamp || conv.created_at)"
          :bg-color="conv.is_client_message ? 'grey-3' : 'primary'"
          :text-color="conv.is_client_message ? 'black' : 'white'"
        />
      </div>
    </div>
    
    <div v-if="!leadStore.conversations.length" class="text-center q-mt-md text-grey">
        No hay conversaciones.
    </div>

  </q-page>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useLeadStore } from 'stores/lead-store';
import { date } from 'quasar';

const route = useRoute();
const leadStore = useLeadStore();

onMounted(async () => {
    const leadId = route.params.id as string;
    await leadStore.fetchLead(leadId);
    await leadStore.fetchConversations(leadId);
});

const formatDate = (val: string) => {
    return date.formatDate(val, 'DD/MM HH:mm');
};
</script>
