<template>
  <span v-if="icon?.type === 'svg'" class="nat-icon nat-icon--svg" aria-hidden="true" v-html="icon.svg" />
  <Icon v-else-if="icon?.type === 'heroicon'" :name="icon.name" type="outline" class="nat-icon" aria-hidden="true" />
</template>

<script setup>
import { getCurrentInstance, onMounted } from 'vue'
import { Icon } from 'laravel-nova-ui'

const props = defineProps({
  // { type: 'heroicon', name } | { type: 'svg', svg } — the svg is sanitized on the server.
  icon: { type: Object, default: null },
})

const instance = getCurrentInstance()

// Nova 5's Icon draws Heroicons v2 names (and the v1 names it aliases). An unknown name
// renders nothing, so say so while debugging instead of leaving an empty button.
onMounted(() => {
  if (props.icon?.type === 'heroicon' && Nova.config('debug') && !instance?.proxy?.$el?.tagName) {
    console.warn(`[nova-action-toolbar] Nova has no Heroicon "${props.icon.name}". Use a raw <svg> instead.`)
  }
})
</script>
