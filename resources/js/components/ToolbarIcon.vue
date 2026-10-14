<template>
  <span v-if="icon?.type === 'svg'" class="nat-icon nat-icon--svg" aria-hidden="true" v-html="icon.svg" />
  <component
    v-else-if="component"
    :is="component"
    class="nat-icon"
    aria-hidden="true"
    width="24"
    height="24"
    viewBox="0 0 24 24"
  />
</template>

<script setup>
import { computed } from 'vue'
import { heroiconComponent } from '../support/icons'

const props = defineProps({
  // { type: 'heroicon', name } | { type: 'svg', svg } — the svg is sanitized on the server.
  icon: { type: Object, default: null },
})

const component = computed(() =>
  props.icon?.type === 'heroicon' ? heroiconComponent(props.icon.name) : null
)
</script>
