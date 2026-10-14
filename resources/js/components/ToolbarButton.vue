<template>
  <component
    :is="item.href ? 'Link' : 'button'"
    :href="item.href"
    :type="item.href ? null : 'button'"
    :class="classes"
    :style="colorStyle"
    :data-nat-key="item.key"
    :dusk="item.dusk"
    :aria-label="accessibleName"
    :aria-disabled="disabled ? 'true' : null"
    :aria-busy="item.busy ? 'true' : null"
    v-tooltip="tooltip"
    @click="handleClick"
  >
    <span v-if="item.busy" class="nat-spinner" aria-hidden="true" />
    <ToolbarIcon v-else-if="item.icon" :icon="item.icon" />
    <span v-if="showLabel">{{ item.label }}</span>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import ToolbarIcon from './ToolbarIcon.vue'

const emit = defineEmits(['activate'])

const props = defineProps({
  // { key, label, display, icon, style, colors, disabledReason, busy, href, dusk }
  item: { type: Object, required: true },
  // Table rows draw icons the way Nova draws view / edit / delete.
  inline: { type: Boolean, default: false },
})

const disabled = computed(
  () => Boolean(props.item.disabled) || Boolean(props.item.disabledReason) || Boolean(props.item.busy)
)

// An icon-only button without a resolvable icon falls back to its label.
const showLabel = computed(() => props.item.display !== 'icon' || !props.item.icon)

const tooltip = computed(() => props.item.disabledReason || (showLabel.value ? null : props.item.label))

// Screen readers get the reason a button is disabled, not only sighted users via the tooltip.
const accessibleName = computed(() => {
  if (props.item.disabledReason) {
    return `${props.item.label} (${props.item.disabledReason})`
  }

  return showLabel.value ? null : props.item.label
})

const classes = computed(() => {
  if (props.inline && !showLabel.value) {
    return ['nat-inline-btn', props.item.style === 'danger' && 'nat-inline-btn--danger']
  }

  return [
    'nat-btn',
    `nat-btn--${props.item.style || 'default'}`,
    !showLabel.value && 'nat-btn--icon',
    props.inline && 'nat-btn--inline',
  ]
})

const colorStyle = computed(() => {
  const colors = props.item.colors

  if (!colors) {
    return null
  }

  const light = colors.light || {}
  const dark = colors.dark || {}

  return {
    '--nat-c-fg': light.text,
    '--nat-c-bg': light.background,
    '--nat-c-bd': light.border,
    '--nat-c-fg-dark': dark.text,
    '--nat-c-bg-dark': dark.background,
    '--nat-c-bd-dark': dark.border,
  }
})

function handleClick(event) {
  // A row click opens the resource; a toolbar button must not.
  event.stopPropagation()

  if (disabled.value) {
    event.preventDefault()
    return
  }

  if (!props.item.href) {
    emit('activate', props.item)
  }
}
</script>
