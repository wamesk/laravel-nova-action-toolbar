<template>
  <div v-if="!suppressed" class="nat-action-dropdown">
    <!-- Confirm Action Modal — the same component Nova's own dropdown opens -->
    <component
      v-if="actionModalVisible"
      :show="actionModalVisible"
      class="text-left"
      :is="selectedAction?.component"
      :working="working"
      :selected-resources="selectedResources"
      :resource-name="resourceName"
      :action="selectedAction"
      :errors="errors"
      @confirm="runAction"
      @close="closeConfirmationModal"
    />

    <component
      v-if="responseModalVisible"
      :show="responseModalVisible"
      :is="actionModalReponseData?.component"
      @confirm="handleResponseModalConfirm"
      @close="handleResponseModalClose"
      :data="actionModalReponseData?.payload ?? {}"
    />

    <ActionToolbar
      :context="resolvedContext"
      :items="pinnedItems"
      :sections="sections"
      :more-position="morePosition"
      :responsive="responsive"
      :trigger-dusk="triggerDuskAttribute"
      :busy-key="loadingKey ? `action:${loadingKey}` : null"
      @activate="activate"
    >
      <template v-if="$slots.menu" #menu><slot name="menu" /></template>
      <template v-if="$slots.trailing" #trailing><slot name="trailing" /></template>
    </ActionToolbar>
  </div>
</template>

<script setup>
import { computed, reactive, toRefs } from 'vue'
import { useStore } from 'vuex'
import { useActions } from '@/composables/useActions'
import { useLocalization } from '@/composables/useLocalization'
import ActionToolbar from './ActionToolbar.vue'
import { BUTTON, ICON, displayOf, hasPinned, isPinned } from '../support/placement'
import { useIndexOwner } from '../support/owner'
import { useRowActionForm } from '../support/useRowActionForm'

/**
 * Drop-in replacement for Nova's ActionDropdown.
 *
 * Without toolbar settings it shows every action in ⋯, exactly like Nova. Actions with
 * `showAsButton()` / `showAsIcon()` for the current context become buttons instead —
 * and leave the menu, so nothing is shown twice. Running an action (confirm modal,
 * toast, redirect, download) is Nova's own useActions, untouched.
 */
const emitter = defineEmits(['actionExecuted'])

const props = defineProps({
  resource: {},
  resourceName: {},
  viaResource: {},
  viaResourceId: {},
  viaRelationship: {},
  relationshipType: {},
  actions: { type: Array, default: () => [] },
  pivotActions: { type: Object, default: null },
  selectedResources: { type: [Array, String], default: () => [] },
  endpoint: { type: String, default: null },
  triggerDuskAttribute: { type: String, default: null },
  showHeadings: { type: Boolean, default: false },

  // Added by this package.
  context: { type: String, default: null },
  // Built-in controls: [{ key, label, display, icon, style, …, onClick | href, dusk }]
  controls: { type: Array, default: () => [] },
  // How many rows the selection stands for (shown in a bulk button's label).
  selectedCount: { type: Number, default: null },
  morePosition: { type: String, default: 'end' },
  responsive: { type: Boolean, default: false },
})

const store = useStore()
const { __ } = useLocalization()
const owner = useIndexOwner()

// In a table row, an action with fields gets its form loaded for that row before it
// opens; Nova's runner then works with the loaded action instead of the baked one.
const {
  actions: runnerActions,
  loadingKey,
  needsRowForm,
  load: loadRowForm,
} = useRowActionForm(props)
const runnerProps = reactive(Object.assign(toRefs(props), { actions: runnerActions }))

const {
  errors,
  actionModalVisible,
  responseModalVisible,
  closeConfirmationModal,
  closeResponseModal,
  handleActionClick,
  selectedAction,
  selectedActionKey,
  working,
  executeAction,
  actionModalReponseData,
} = useActions(runnerProps, emitter, store)

const resolvedContext = computed(() => props.context || 'index')

// Nova's own header dropdown on a resource index steps aside when this package's
// header toolbar (rendered next to the create button) takes over the whole header.
const suppressed = computed(
  () =>
    props.context === null &&
    props.triggerDuskAttribute === 'index-standalone-action-dropdown' &&
    owner !== null &&
    !owner.isLensView &&
    hasPinned(owner.allActions, 'index')
)

const allActions = computed(() => props.actions.concat(props.pivotActions?.actions || []))

const selectionIsEmpty = computed(
  () => props.selectedResources !== 'all' && (props.selectedResources?.length ?? 0) === 0
)

const actionItem = action => {
  const bulk = resolvedContext.value === 'index' && action.standalone !== true
  const count = props.selectedCount ?? (Array.isArray(props.selectedResources) ? props.selectedResources.length : null)

  return {
    key: `action:${action.uriKey}`,
    uriKey: action.uriKey,
    label: bulk && !selectionIsEmpty.value && count ? `${action.name} (${count})` : action.name,
    display: displayOf(action.toolbar, resolvedContext.value),
    icon: action.toolbar?.icon || null,
    style: action.toolbar?.style || null,
    colors: action.toolbar?.colors || null,
    priority: action.toolbar?.priority ?? 0,
    disabled: action.authorizedToRun === false,
    disabledReason: bulk && selectionIsEmpty.value ? __('Select at least one row first') : null,
    busy:
      (working.value && selectedActionKey.value === action.uriKey) ||
      loadingKey.value === action.uriKey,
    dusk: `${action.uriKey}-toolbar-button`,
  }
}

const controlIsPinned = control => control.display === BUTTON || control.display === ICON

const pinnedItems = computed(() => [
  ...allActions.value.filter(action => isPinned(action, resolvedContext.value)).map(actionItem),
  ...props.controls.filter(controlIsPinned),
])

// On the index the header shows standalone actions only; bulk actions stay in Nova's
// select above the table unless a developer pinned them here.
const menuActions = computed(() =>
  props.actions.filter(
    action =>
      !isPinned(action, resolvedContext.value) &&
      (resolvedContext.value !== 'index' || action.standalone === true)
  )
)

const sections = computed(() => [
  {
    key: 'controls',
    heading: props.showHeadings ? __('Actions') : null,
    items: props.controls.filter(control => !controlIsPinned(control)),
  },
  {
    key: 'actions',
    heading: props.showHeadings ? __('User Actions') : null,
    items: menuActions.value.map(action => ({
      key: `action:${action.uriKey}`,
      actionId: action.uriKey,
      label: action.name,
      disabled: action.authorizedToRun === false,
      onClick: () => handleClick(action),
    })),
  },
])

const handleClick = action => {
  if (action.authorizedToRun === false || loadingKey.value !== null) {
    return
  }

  if (!needsRowForm(action)) {
    handleActionClick(action.uriKey)
    return
  }

  loadRowForm(action).then(open => open && handleActionClick(action.uriKey))
}

const activate = item => {
  if (item.disabled || item.disabledReason) {
    return
  }

  if (item.uriKey) {
    const action = allActions.value.find(candidate => candidate.uriKey === item.uriKey)

    if (action) {
      handleClick(action)
    }

    return
  }

  item.onClick?.()
}

const runAction = () => executeAction(() => emitter('actionExecuted'))

const handleResponseModalConfirm = () => {
  closeResponseModal()
  emitter('actionExecuted')
}

const handleResponseModalClose = () => {
  closeResponseModal()
  emitter('actionExecuted')
}
</script>
