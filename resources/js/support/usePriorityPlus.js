import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

/**
 * Priority+ overflow: when the toolbar is wider than its space, buttons move into ⋯ —
 * the lowest priority first, and among equal priorities the last one first.
 *
 * Buttons are hidden through the `hidden` attribute and measured right away, so the
 * layout settles in one frame without re-rendering Vue for every step.
 *
 * @param {import('vue').Ref<HTMLElement|null>} root  the toolbar element
 * @param {() => Array<{key: string, priority?: number}>} items  pinned items in display order
 * @param {() => boolean} enabled
 */
export function usePriorityPlus(root, items, enabled) {
  const overflow = ref([])
  let observer = null
  let frame = null

  const layout = () => {
    const element = root.value

    if (!element) {
      return
    }

    const group = element.querySelector(':scope > .nat-group')
    const buttons = Array.from(element.querySelectorAll('[data-nat-key]'))
    buttons.forEach(button => (button.hidden = false))

    if (group) {
      group.hidden = false
    }

    if (!enabled()) {
      overflow.value = []
      markEnds(buttons)
      return
    }

    const list = items()
    const order = list
      .map((item, index) => ({ key: item.key, priority: item.priority ?? 0, index }))
      .sort((a, b) => a.priority - b.priority || b.index - a.index)

    const hidden = []

    for (const candidate of order) {
      if (element.scrollWidth <= element.clientWidth + 1) {
        break
      }

      const button = buttons.find(b => b.dataset.natKey === candidate.key)

      if (button) {
        button.hidden = true
        hidden.push(candidate.key)
      }
    }

    markEnds(buttons)

    // With every button in ⋯ the strip disappears, and ⋯ looks like Nova's plain trigger.
    if (group) {
      group.hidden = buttons.every(button => button.hidden)
    }

    const changed =
      hidden.length !== overflow.value.length ||
      hidden.some(key => !overflow.value.includes(key))

    if (changed) {
      overflow.value = list.map(item => item.key).filter(key => hidden.includes(key))
    }
  }

  // Layout B rounds only the outer corners of the visible run of buttons.
  const markEnds = buttons => {
    const visible = buttons.filter(button => !button.hidden)
    buttons.forEach(button => button.classList.remove('nat-is-first', 'nat-is-last'))
    visible[0]?.classList.add('nat-is-first')
    visible[visible.length - 1]?.classList.add('nat-is-last')
  }

  const schedule = () => {
    cancelAnimationFrame(frame)
    frame = requestAnimationFrame(layout)
  }

  onMounted(() => {
    nextTick(layout)

    if (typeof ResizeObserver !== 'undefined' && root.value) {
      observer = new ResizeObserver(schedule)
      observer.observe(root.value)
    }
  })

  watch(items, () => nextTick(layout), { deep: true })

  // The ⋯ trigger appears once something overflows and takes space itself, so measure again.
  watch(
    () => overflow.value.length > 0,
    () => nextTick(layout)
  )

  onBeforeUnmount(() => {
    cancelAnimationFrame(frame)
    observer?.disconnect()
  })

  return { overflow, relayout: layout }
}
