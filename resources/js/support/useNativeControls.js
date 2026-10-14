import { getCurrentInstance, nextTick, onBeforeUnmount, onMounted, reactive, watch } from 'vue'

/**
 * Nova draws some built-in controls itself, next to the dropdown this package replaces:
 * view / edit / delete / restore in a table row, view / edit on the detail. Copying those
 * templates would tie the package to one Nova version, so they stay Nova's:
 *
 * - a control the developer moved is hidden by CSS (`data-nat-hide` on the shared parent);
 * - its new button or menu item clicks Nova's hidden element, so Nova's own link,
 *   modal and authorization run unchanged;
 * - a control Nova did not render (not authorized) is not offered at all.
 *
 * @param {() => Object<string, string[]>} selectors  control name → dusk values of Nova's element
 * @param {() => string[]} taken  control names this toolbar took over
 */
export function useNativeControls(selectors, taken) {
  const instance = getCurrentInstance()
  const natives = reactive({})
  let observer = null

  const parent = () => instance?.proxy?.$el?.parentElement ?? null

  const find = name => {
    const element = parent()
    const values = selectors()[name] || []

    for (const value of values) {
      const match = element ? Array.from(element.children).find(child => child.getAttribute('dusk') === value) : null

      if (match) {
        return match
      }
    }

    return null
  }

  const sync = () => {
    const element = parent()

    if (!element) {
      return
    }

    const names = taken()

    if (names.length > 0) {
      element.dataset.natHide = names.join(' ')
    } else {
      delete element.dataset.natHide
    }

    for (const name of Object.keys(selectors())) {
      const native = find(name)
      const present = native !== null
      const disabled = Boolean(native?.hasAttribute('disabled'))

      if (natives[name]?.present !== present || natives[name]?.disabled !== disabled) {
        natives[name] = { present, disabled }
      }
    }
  }

  onMounted(() => {
    nextTick(sync)

    const element = parent()

    if (element && typeof MutationObserver !== 'undefined') {
      // Nova re-renders its own buttons (soft delete → restore, policy refresh).
      observer = new MutationObserver(sync)
      observer.observe(element, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] })
    }
  })

  watch(taken, () => nextTick(sync))

  onBeforeUnmount(() => {
    observer?.disconnect()

    const element = parent()

    if (element) {
      delete element.dataset.natHide
    }
  })

  return {
    natives,
    click: name => find(name)?.click(),
  }
}
