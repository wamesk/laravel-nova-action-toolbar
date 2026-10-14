<template>
  <ActionDropdown
    v-if="resource"
    context="detail"
    :responsive="true"
    :resource="resource"
    :actions="actions"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :via-relationship="viaRelationship"
    :resource-name="resourceName"
    :selected-resources="[resource.id.value]"
    :trigger-dusk-attribute="`${resource.id.value}-control-selector`"
    :show-headings="true"
    :controls="visibleControls"
    @actionExecuted="$emit('actionExecuted')"
  />

  <DeleteResourceModal
    mode="delete"
    :resource-name="resourceName"
    :show="deleteModalOpen"
    @close="closeDeleteModal"
    @confirm="confirmDelete"
  />

  <RestoreResourceModal
    :resource-name="resourceName"
    :show="restoreModalOpen"
    @close="closeRestoreModal"
    @confirm="confirmRestore"
  />

  <DeleteResourceModal
    mode="force delete"
    :resource-name="resourceName"
    :show="forceDeleteModalOpen"
    @close="closeForceDeleteModal"
    @confirm="confirmForceDelete"
  />
</template>

<script>
import { ref } from 'vue'
import { mapActions, mapGetters } from 'vuex'
import Deletable from '@/mixins/Deletable'
import InteractsWithResourceInformation from '@/mixins/InteractsWithResourceInformation'
import { mapProps } from '@/mixins/propTypes'
import { useNativeControls } from '../support/useNativeControls'
import { CONTROL_ICONS, controlItem, isTakenOver } from '../support/controls'
import { controlSettings } from '../support/config'

/**
 * Drop-in replacement for Nova's DetailActionDropdown.
 *
 * The delete / restore / force delete flow (modals, toasts, redirects) is Nova's own
 * code, kept as Nova 5 ships it. Only the menu is built from data, so each built-in
 * control can move to a button or an icon. View and Edit are drawn by Nova's detail
 * header next to this component; a moved one is hidden and clicked through.
 */
export default {
  emits: ['actionExecuted', 'resource-deleted', 'resource-restored'],

  inheritAttrs: false,

  mixins: [Deletable, InteractsWithResourceInformation],

  props: {
    resource: { type: Object },
    actions: { type: Array },
    viaManyToMany: { type: Boolean },

    ...mapProps(['resourceName', 'viaResource', 'viaResourceId', 'viaRelationship']),
  },

  setup() {
    const takenOver = ref([])

    const { natives, click } = useNativeControls(
      () => ({
        view: ['view-resource-button'],
        edit: ['edit-resource-button'],
      }),
      () => takenOver.value
    )

    return { natives, clickNative: click, takenOver }
  },

  data: () => ({
    deleteModalOpen: false,
    restoreModalOpen: false,
    forceDeleteModalOpen: false,
  }),

  computed: {
    ...mapGetters(['currentUser']),

    settings() {
      return controlSettings(this.resourceName)
    },

    controls() {
      if (!this.resource) {
        return []
      }

      const id = this.resource.id.value
      const resource = this.resource

      const list = [
        resource.authorizedToReplicate &&
          controlItem('replicate', this.settings.replicate, 'detail', {
            label: this.__('Replicate'),
            icon: CONTROL_ICONS.replicate,
            dusk: `${id}-replicate-button`,
            href: this.$url(`/resources/${this.resourceName}/${id}/replicate`, {
              viaResource: this.viaResource,
              viaResourceId: this.viaResourceId,
              viaRelationship: this.viaRelationship,
            }),
          }),

        this.currentUser.canImpersonate &&
          resource.authorizedToImpersonate &&
          controlItem('impersonate', this.settings.impersonate, 'detail', {
            label: this.__('Impersonate'),
            icon: CONTROL_ICONS.impersonate,
            dusk: `${id}-impersonate-button`,
            onClick: () => this.startImpersonating({ resource: this.resourceName, resourceId: id }),
          }),

        resource.authorizedToDelete &&
          !resource.softDeleted &&
          controlItem('delete', this.settings.delete, 'detail', {
            label: this.__('Delete :resource', { resource: this.resourceInformation.singularLabel }),
            icon: CONTROL_ICONS.delete,
            dusk: 'open-delete-modal-button',
            onClick: () => this.openDeleteModal(),
          }),

        resource.authorizedToRestore &&
          resource.softDeleted &&
          controlItem('restore', this.settings.restore, 'detail', {
            label: this.__('Restore :resource', { resource: this.resourceInformation.singularLabel }),
            icon: CONTROL_ICONS.restore,
            dusk: 'open-restore-modal-button',
            onClick: () => this.openRestoreModal(),
          }),

        resource.authorizedToForceDelete &&
          controlItem('forceDelete', this.settings.forceDelete, 'detail', {
            label: this.__('Force Delete :resource', { resource: this.resourceInformation.singularLabel }),
            icon: CONTROL_ICONS.forceDelete,
            dusk: 'open-force-delete-modal-button',
            onClick: () => this.openForceDeleteModal(),
          }),

        this.nativeItem('view', this.__('View')),
        this.nativeItem('edit', this.__('Edit')),
      ]

      return list.filter(Boolean)
    },

    visibleControls() {
      return this.controls.filter(control => control.display !== 'native')
    },

    takenOverNames() {
      return this.controls
        .filter(control => ['view', 'edit'].includes(control.control))
        .filter(isTakenOver)
        .map(control => control.control)
    },
  },

  watch: {
    takenOverNames: {
      immediate: true,
      handler(names) {
        this.takenOver = names
      },
    },
  },

  methods: {
    ...mapActions(['startImpersonating']),

    nativeItem(name, label) {
      const item = controlItem(name, this.settings[name], 'detail', {
        label,
        icon: CONTROL_ICONS[name],
        dusk: `${name}-resource-toolbar`,
        onClick: () => this.clickNative(name),
      })

      if (item.display === 'native') {
        return item
      }

      return this.natives[name]?.present ? item : null
    },

    // ↓ Nova 5 DetailActionDropdown, unchanged.

    async confirmDelete() {
      this.deleteResources([this.resource], response => {
        Nova.success(
          this.__('The :resource was deleted!', {
            resource: this.resourceInformation.singularLabel.toLowerCase(),
          })
        )

        if (response && response.data && response.data.redirect) {
          Nova.visit(response.data.redirect)
          return
        }

        if (!this.resource.softDeletes) {
          Nova.visit(`/resources/${this.resourceName}`)
          return
        }

        this.closeDeleteModal()
        this.$emit('resource-deleted')
      })
    },

    openDeleteModal() {
      this.deleteModalOpen = true
    },

    closeDeleteModal() {
      this.deleteModalOpen = false
    },

    async confirmRestore() {
      this.restoreResources([this.resource], () => {
        Nova.success(
          this.__('The :resource was restored!', {
            resource: this.resourceInformation.singularLabel.toLowerCase(),
          })
        )

        this.closeRestoreModal()
        this.$emit('resource-restored')
      })
    },

    openRestoreModal() {
      this.restoreModalOpen = true
    },

    closeRestoreModal() {
      this.restoreModalOpen = false
    },

    async confirmForceDelete() {
      this.forceDeleteResources([this.resource], response => {
        Nova.success(
          this.__('The :resource was deleted!', {
            resource: this.resourceInformation.singularLabel.toLowerCase(),
          })
        )

        if (response && response.data && response.data.redirect) {
          Nova.visit(response.data.redirect)
          return
        }

        Nova.visit(`/resources/${this.resourceName}`)
      })
    },

    openForceDeleteModal() {
      this.forceDeleteModalOpen = true
    },

    closeForceDeleteModal() {
      this.forceDeleteModalOpen = false
    },
  },
}
</script>
