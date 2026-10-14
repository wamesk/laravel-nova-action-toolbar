import { BUTTON, ICON, MENU, controlDisplay } from './placement'

/**
 * Turn a built-in control into a toolbar item.
 *
 * @param {string} name  control name ("delete", "replicate", …)
 * @param {Object} settings  the resource's Control settings for this name (may be undefined)
 * @param {string} context
 * @param {Object} item  { label, icon, onClick | href, dusk, disabled, style }
 */
export function controlItem(name, settings, context, item) {
  const display = controlDisplay(name, settings, context)

  return {
    key: `control:${name}`,
    control: name,
    ...item,
    display: display === 'native' ? 'native' : display,
    icon: settings?.icon || item.icon || null,
    style: settings?.style || item.style || null,
    colors: settings?.colors || null,
    priority: settings?.priority ?? 0,
  }
}

/**
 * True when a native control was moved away from where Nova draws it.
 */
export function isTakenOver(item) {
  return item.display === BUTTON || item.display === ICON || item.display === MENU
}

/** Heroicons for the built-in controls when shown as a button or an icon. */
export const CONTROL_ICONS = {
  preview: { type: 'heroicon', name: 'eye' },
  replicate: { type: 'heroicon', name: 'document-duplicate' },
  impersonate: { type: 'heroicon', name: 'finger-print' },
  view: { type: 'heroicon', name: 'eye' },
  edit: { type: 'heroicon', name: 'pencil-square' },
  delete: { type: 'heroicon', name: 'trash' },
  restore: { type: 'heroicon', name: 'arrow-path' },
  forceDelete: { type: 'heroicon', name: 'trash' },
}
