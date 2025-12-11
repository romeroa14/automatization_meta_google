<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Campañas Activas</div>

    <div class="row q-col-gutter-md">
      <div v-for="campaign in campaignStore.campaigns" :key="campaign.id" class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-h6">{{ campaign.meta_campaign_name }}</div>
            <div class="text-caption text-grey">{{ campaign.campaign_status }}</div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="row items-center justify-between">
              <div>Gasto:</div>
              <div class="text-weight-bold">${{ campaign.amount_spent }}</div>
            </div>
            <div class="row items-center justify-between">
               <div>Presupuesto Diario:</div>
               <div>${{ campaign.campaign_daily_budget }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    
    <div v-if="campaignStore.loading" class="flex flex-center q-pa-lg">
        <q-spinner color="primary" size="3em" />
    </div>

  </q-page>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useCampaignStore } from 'stores/campaign-store';

const campaignStore = useCampaignStore();

onMounted(() => {
  campaignStore.fetchCampaigns();
});
</script>
