<template>
  <q-item clickable :tag="props.internal ? 'router-link' : 'a'" :to="props.internal ? props.link : undefined" :href="!props.internal ? props.link : undefined" :target="props.internal ? undefined : '_blank'">
    <q-item-section v-if="props.icon" avatar>
      <q-icon :name="props.icon" />
    </q-item-section>

    <q-item-section>
      <q-item-label>{{ props.title }}</q-item-label>
      <q-item-label caption>{{ props.caption }}</q-item-label>
    </q-item-section>
  </q-item>
</template>

<script setup lang="ts">
export interface EssentialLinkProps {
  title: string
  caption?: string
  link?: string
  icon?: string
  internal?: boolean
}

const props = withDefaults(defineProps<EssentialLinkProps>(), {
  caption: '',
  link: '#',
  icon: '',
  internal: false
})
</script>
