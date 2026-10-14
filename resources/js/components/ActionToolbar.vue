<template>
  <div
    ref="root"
    class="nat-toolbar"
    :class="[
      `nat-toolbar--${effectiveLayout}`,
      `nat-toolbar--${context}`,
      { 'nat-toolbar--responsive': responsive },
    ]"
    role="group"
    :aria-label="__('Actions')"
  >
    <div v-if="responsive" class="nat-toolbar__spacer" />

    <MoreMenu
      v-if="morePosition === 'start' && showMore"
      :sections="sections"
      :overflow-items="overflowItems"
      :trigger-dusk="triggerDusk"
      :inline="context === 'inline'"
      @activate="item => $emit('activate', item)"
    >
      <template v-if="$slots.menu" #menu><slot name="menu" /></template>
    </MoreMenu>

    <div v-if="items.length > 0" class="nat-group">
      <ToolbarButton
        v-for="item in items"
        :key="item.key"
        :item="item"
        :inline="context === 'inline'"
        @activate="$emit('activate', item)"
      />
    </div>

    <MoreMenu
      v-if="morePosition === 'end' && showMore"
      :sections="sections"
      :overflow-items="overflowItems"
      :trigger-dusk="triggerDusk"
      :inline="context === 'inline'"
      @activate="item => $emit('activate', item)"
    >
      <template v-if="$slots.menu" #menu><slot name="menu" /></template>
    </MoreMenu>

    <slot name="trailing" />
  </div>
</template>

<script setup>
import { computed, ref, useSlots } from 'vue'
import ToolbarButton from './ToolbarButton.vue'
import MoreMenu from './MoreMenu.vue'
import { toolbarLayout } from '../support/config'
import { usePriorityPlus } from '../support/usePriorityPlus'

defineEmits(['activate'])

const props = defineProps({
  context: { type: String, required: true },
  // Pinned items: [{ key, label, display, icon, style, colors, priority, disabledReason, busy, href, dusk }]
  items: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  morePosition: { type: String, default: 'end' },
  responsive: { type: Boolean, default: false },
  triggerDusk: { type: String, default: null },
})

const slots = useSlots()
const root = ref(null)

// Rows always look like Nova's row icons; only the index and the detail group buttons.
const effectiveLayout = computed(() => (props.context === 'inline' ? 'separate' : toolbarLayout()))

const { overflow } = usePriorityPlus(
  root,
  () => props.items.map(item => ({ key: item.key, priority: item.priority })),
  () => props.responsive
)

const overflowItems = computed(() => props.items.filter(item => overflow.value.includes(item.key)))

// ⋯ disappears when nothing is left in it.
const showMore = computed(
  () =>
    overflowItems.value.length > 0 ||
    props.sections.some(section => section.items.length > 0) ||
    Boolean(slots.menu)
)
</script>
