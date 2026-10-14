/**
 * Settings the service provider hands to the page (Nova.config('actionToolbar')).
 */
export function toolbarConfig() {
  return Nova.config('actionToolbar') || { layout: 'grouped', controls: {} }
}

export function toolbarLayout() {
  return toolbarConfig().layout === 'separate' ? 'separate' : 'grouped'
}

/**
 * Control settings of one resource, keyed by control name ("delete", "replicate", …).
 *
 * @returns {Object<string, Object>}
 */
export function controlSettings(resourceName) {
  const list = toolbarConfig().controls?.[resourceName] || []

  // The same control listed twice (one entry per context) is merged; the later entry wins
  // for anything both set.
  return list.reduce((settings, control) => {
    const previous = settings[control.control]

    settings[control.control] = previous
      ? { ...previous, ...control, display: { ...previous.display, ...control.display } }
      : control

    return settings
  }, {})
}
