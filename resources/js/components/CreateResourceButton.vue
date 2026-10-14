<template>
  <ActionDropdown
    v-if="active"
    context="index"
    :responsive="true"
    :resource-name="owner.resourceName"
    :via-resource="owner.viaResource"
    :via-resource-id="owner.viaResourceId"
    :via-relationship="owner.viaRelationship"
    :relationship-type="owner.relationshipType"
    :actions="owner.actions"
    :pivot-actions="owner.pivotActions"
    :selected-resources="owner.selectedResourcesForActionSelector"
    :selected-count="selectedCount"
    trigger-dusk-attribute="index-standalone-action-dropdown"
    @actionExecuted="owner.handleActionExecuted()"
  >
    <template #trailing>
      <NovaCreateResourceButton v-bind="$attrs" />
    </template>
  </ActionDropdown>

  <NovaCreateResourceButton v-else v-bind="$attrs" />
</template>

<script>
import NovaCreateResourceButton from '@/components/CreateResourceButton.vue'
import { hasPinned } from '../support/placement'
import { useIndexOwner } from '../support/owner'

/**
 * Nova's create button, plus — when a resource pins actions on the index — the whole
 * header toolbar in front of it.
 *
 * Nova 4 renders its header action dropdown only when the resource has standalone
 * actions, and passes it only those. A bulk action shown as a button needs the selected
 * rows and must be visible (disabled) before anything is selected, so the toolbar lives
 * here, next to the create button that the header always renders, and reads the index
 * state from the index view itself.
 */
export default {
  components: { NovaCreateResourceButton },

  inheritAttrs: false,

  setup() {
    return { owner: useIndexOwner() }
  },

  computed: {
    active() {
      return (
        this.owner !== null &&
        !this.owner.isLensView &&
        hasPinned(this.owner.allActions, 'index')
      )
    },

    selectedCount() {
      if (!this.owner) {
        return null
      }

      return this.owner.selectAllMatchingChecked
        ? this.owner.allMatchingResourceCount
        : this.owner.selectedResources.length
    },
  },
}
</script>
