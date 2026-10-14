export const BUTTON = 'button'
export const ICON = 'icon'
export const MENU = 'menu'

/**
 * Where Nova itself draws each built-in control today. "native" controls are drawn by
 * Nova's own row / detail template, outside the dropdown this package replaces.
 */
export const NOVA_DEFAULTS = {
  inline: {
    preview: MENU,
    replicate: MENU,
    impersonate: MENU,
    view: 'native',
    edit: 'native',
    delete: 'native',
    restore: 'native',
  },
  detail: {
    replicate: MENU,
    impersonate: MENU,
    delete: MENU,
    restore: MENU,
    forceDelete: MENU,
    view: 'native',
    edit: 'native',
  },
}

/**
 * The display the developer set for one context, or null when nothing was set.
 */
export function displayOf(settings, context) {
  return settings?.display?.[context] ?? null
}

/**
 * True when an action is drawn as a button or an icon in the given context.
 */
export function isPinned(action, context) {
  const display = displayOf(action.toolbar, context)

  return display === BUTTON || display === ICON
}

/**
 * Final display of a built-in control: the developer's setting, else Nova's default.
 */
export function controlDisplay(controlName, settings, context) {
  return displayOf(settings, context) ?? NOVA_DEFAULTS[context]?.[controlName] ?? MENU
}

/**
 * True when any action of the list is configured for the toolbar in this context.
 */
export function hasPinned(actions, context) {
  return (actions || []).some(action => isPinned(action, context))
}
