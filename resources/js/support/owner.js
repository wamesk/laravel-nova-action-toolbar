import { getCurrentInstance } from 'vue'

/**
 * The resource index (or lens) component that renders the calling component.
 *
 * Nova keeps the index state — all actions, the selected rows, the endpoint —
 * in its Index / Lens view and passes only parts of it down. The header toolbar
 * and the bulk select need the whole picture, so they read it from the view.
 */
export function useIndexOwner() {
  let parent = getCurrentInstance()?.parent

  while (parent) {
    const proxy = parent.proxy

    if (proxy && 'allActions' in proxy && 'selectedResources' in proxy) {
      return proxy
    }

    parent = parent.parent
  }

  return null
}

/**
 * True when Nova renders the header block above the table (the same condition as
 * Nova's Index.vue). Only then can a bulk action live there as a button.
 *
 * Newer Nova 5 releases ask their own `hasResourceActionControls` (create only on a plain
 * index, attach only on a relation); earlier 5.x releases use the Nova 4 condition.
 */
export function ownerHasHeader(owner) {
  if (!owner || owner.isLensView) {
    return false
  }

  if ('hasResourceActionControls' in owner) {
    return Boolean(owner.hasResourceActionControls)
  }

  return (
    (owner.availableStandaloneActions?.length ?? 0) > 0 ||
    Boolean(owner.authorizedToCreate) ||
    Boolean(owner.authorizedToRelate)
  )
}
