<template>
  <NovaActionSelector v-bind="$attrs" :actions="selectableActions" :pivot-actions="selectablePivotActions" />
</template>

<script>
import NovaActionSelector from '@/components/ActionSelector.vue'
import { isPinned } from '../support/placement'
import { ownerHasHeader, useIndexOwner } from '../support/owner'

/**
 * Nova's select for the checked rows, minus the bulk actions that sit as a button
 * in the header — an action is never offered twice. When Nova draws no header (the user
 * may not create and there is no standalone action) the select keeps every action,
 * so nothing becomes unreachable.
 */
export default {
  components: { NovaActionSelector },

  inheritAttrs: false,

  props: {
    actions: { type: Array, default: () => [] },
    pivotActions: { type: Object, default: () => ({ name: 'Pivot', actions: [] }) },
  },

  setup() {
    return { owner: useIndexOwner() }
  },

  computed: {
    selectableActions() {
      return this.withoutPinned(this.actions)
    },

    selectablePivotActions() {
      return { ...this.pivotActions, actions: this.withoutPinned(this.pivotActions?.actions || []) }
    },
  },

  methods: {
    withoutPinned(actions) {
      if (!ownerHasHeader(this.owner)) {
        return actions
      }

      return actions.filter(action => !isPinned(action, 'index'))
    },
  },
}
</script>
