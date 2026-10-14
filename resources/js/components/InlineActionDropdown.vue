<template>
  <ActionDropdown
    context="inline"
    more-position="start"
    :actions="actions"
    :endpoint="endpoint"
    :via-resource="viaResource"
    :via-resource-id="viaResourceId"
    :via-relationship="viaRelationship"
    :resource-name="resourceName"
    :selected-resources="[resource.id.value]"
    :show-headings="true"
    :controls="visibleControls"
    :trigger-dusk-attribute="`${resource.id.value}-control-selector`"
    @actionExecuted="$emit('actionExecuted')"
  />
</template>

<script>
import { ref } from 'vue'
import { mapActions, mapGetters } from 'vuex'
import { mapProps } from '@/mixins/propTypes'
import { useNativeControls } from '../support/useNativeControls'
import { CONTROL_ICONS, controlItem, isTakenOver } from '../support/controls'
import { controlSettings } from '../support/config'

/**
 * Drop-in replacement for Nova's InlineActionDropdown (the ⋯ in a table row).
 *
 * Preview, Replicate and Impersonate are drawn here, as in Nova. View, Edit, Delete and
 * Restore are drawn by Nova's row template next to it; when a resource moves one of them,
 * this component hides Nova's element and offers a button or menu item that clicks it.
 */
export default {
  emits: ['actionExecuted', 'show-preview'],

  props: {
    resource: { type: Object },
    actions: { type: Array },
    viaManyToMany: { type: Boolean },
    endpoint: { type: String, default: null },

    ...mapProps(['resourceName', 'viaResource', 'viaResourceId', 'viaRelationship']),
  },

  setup(props) {
    const id = () => props.resource.id.value
    const takenOver = ref([])

    const { natives, click } = useNativeControls(
      () => ({
        view: [`${id()}-view-button`],
        edit: [`${id()}-edit-button`, `${id()}-edit-attached-button`],
        delete: [`${id()}-delete-button`],
        restore: [`${id()}-restore-button`],
      }),
      // Filled by the takenOverNames watcher below.
      () => takenOver.value
    )

    return { natives, clickNative: click, takenOver }
  },

  computed: {
    ...mapGetters(['currentUser']),

    settings() {
      return controlSettings(this.resourceName)
    },

    controls() {
      const id = this.resource.id.value
      const viaQuery = {
        viaResource: this.viaResource,
        viaResourceId: this.viaResourceId,
        viaRelationship: this.viaRelationship,
      }

      const list = [
        this.resource.authorizedToView &&
          this.resource.previewHasFields &&
          controlItem('preview', this.settings.preview, 'inline', {
            label: this.__('Preview'),
            icon: CONTROL_ICONS.preview,
            dusk: `${id}-preview-button`,
            onClick: () => this.$emit('show-preview'),
          }),

        this.resource.authorizedToReplicate &&
          controlItem('replicate', this.settings.replicate, 'inline', {
            label: this.__('Replicate'),
            icon: CONTROL_ICONS.replicate,
            dusk: `${id}-replicate-button`,
            href: this.$url(`/resources/${this.resourceName}/${id}/replicate`, viaQuery),
          }),

        this.currentUser.canImpersonate &&
          this.resource.authorizedToImpersonate &&
          controlItem('impersonate', this.settings.impersonate, 'inline', {
            label: this.__('Impersonate'),
            icon: CONTROL_ICONS.impersonate,
            dusk: `${id}-impersonate-button`,
            onClick: () =>
              this.startImpersonating({ resource: this.resourceName, resourceId: id }),
          }),

        this.nativeItem('view', this.__('View')),
        this.nativeItem('edit', this.viaManyToMany ? this.__('Edit Attached') : this.__('Edit')),
        this.nativeItem('delete', this.__(this.viaManyToMany ? 'Detach' : 'Delete')),
        this.nativeItem('restore', this.__('Restore')),
      ]

      return list.filter(Boolean)
    },

    visibleControls() {
      return this.controls.filter(control => control.display !== 'native')
    },

    takenOverNames() {
      return this.controls
        .filter(control => ['view', 'edit', 'delete', 'restore'].includes(control.control))
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

    /**
     * A control Nova draws in the row. It is offered only when Nova rendered it,
     * and disabled when Nova's own element is disabled.
     */
    nativeItem(name, label) {
      const settings = this.settings[name]
      const item = controlItem(name, settings, 'inline', {
        label,
        icon: CONTROL_ICONS[name],
        style: settings?.style || null,
        dusk: `${this.resource.id.value}-${name}-toolbar`,
        onClick: () => this.clickNative(name),
      })

      if (item.display === 'native') {
        return item
      }

      const native = this.natives[name]

      if (!native?.present) {
        return null
      }

      return { ...item, disabled: native.disabled }
    },
  },
}
</script>
