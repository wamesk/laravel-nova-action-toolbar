import upperFirst from 'lodash/upperFirst'
import camelCase from 'lodash/camelCase'

let app = null

/**
 * Remember the Vue app so icon names can be resolved against Nova's registered icons.
 */
export function rememberApp(vueApp) {
  app = vueApp
}

/**
 * Nova 4 registers Heroicons v1 as global components (`HeroiconsOutlineDocumentDownload`).
 * Nova 5 and the Heroicons website use v2 names (`document-arrow-down`). The same PHP
 * code must work on both, so a v2 name is translated to its v1 twin here.
 */
const V2_TO_V1 = {
  'adjustments-vertical': 'adjustments',
  'archive-box': 'archive',
  'arrow-down-circle': 'arrow-circle-down',
  'arrow-left-circle': 'arrow-circle-left',
  'arrow-right-circle': 'arrow-circle-right',
  'arrow-up-circle': 'arrow-circle-up',
  'arrow-long-down': 'arrow-narrow-down',
  'arrow-long-left': 'arrow-narrow-left',
  'arrow-long-right': 'arrow-narrow-right',
  'arrow-long-up': 'arrow-narrow-up',
  'arrow-down-tray': 'download',
  'arrow-up-tray': 'upload',
  'arrow-path': 'refresh',
  'arrow-top-right-on-square': 'external-link',
  'arrow-trending-down': 'trending-down',
  'arrow-trending-up': 'trending-up',
  'arrow-uturn-left': 'reply',
  'arrows-pointing-out': 'arrows-expand',
  'arrows-right-left': 'switch-horizontal',
  'arrows-up-down': 'switch-vertical',
  'banknotes': 'cash',
  'bars-3': 'menu',
  'bars-arrow-down': 'sort-descending',
  'bars-arrow-up': 'sort-ascending',
  'bolt': 'lightning-bolt',
  'building-library': 'library',
  'building-office': 'office-building',
  'chat-bubble-bottom-center-text': 'annotation',
  'chat-bubble-left': 'chat-alt',
  'chat-bubble-left-right': 'chat-alt-2',
  'chat-bubble-oval-left': 'chat',
  'check-badge': 'badge-check',
  'chevron-up-down': 'selector',
  'circle-stack': 'database',
  'clipboard-document': 'clipboard-copy',
  'clipboard-document-check': 'clipboard-check',
  'clipboard-document-list': 'clipboard-list',
  'cloud-arrow-down': 'cloud-download',
  'cloud-arrow-up': 'cloud-upload',
  'code-bracket': 'code',
  'cog-6-tooth': 'cog',
  'cpu-chip': 'chip',
  'cursor-arrow-rays': 'cursor-click',
  'device-phone-mobile': 'device-mobile',
  'document-arrow-down': 'document-download',
  'document-chart-bar': 'document-report',
  'document-duplicate': 'duplicate',
  'document-magnifying-glass': 'document-search',
  'document-minus': 'document-remove',
  'document-plus': 'document-add',
  'ellipsis-horizontal': 'dots-horizontal',
  'ellipsis-vertical': 'dots-vertical',
  'envelope': 'mail',
  'envelope-open': 'mail-open',
  'exclamation-triangle': 'exclamation',
  'eye-slash': 'eye-off',
  'face-frown': 'emoji-sad',
  'face-smile': 'emoji-happy',
  'folder-arrow-down': 'folder-download',
  'folder-minus': 'folder-remove',
  'folder-plus': 'folder-add',
  'funnel': 'filter',
  'globe-americas': 'globe',
  'hand-raised': 'hand',
  'inbox-arrow-down': 'inbox-in',
  'language': 'translate',
  'magnifying-glass': 'search',
  'magnifying-glass-circle': 'search-circle',
  'magnifying-glass-minus': 'zoom-out',
  'magnifying-glass-plus': 'zoom-in',
  'map-pin': 'location-marker',
  'megaphone': 'speakerphone',
  'musical-note': 'music',
  'pencil-square': 'pencil-alt',
  'phone-arrow-down-left': 'phone-incoming',
  'phone-arrow-up-right': 'phone-outgoing',
  'phone-x-mark': 'phone-missed-call',
  'photo': 'photograph',
  'puzzle-piece': 'puzzle',
  'qr-code': 'qrcode',
  'queue-list': 'view-list',
  'receipt-percent': 'receipt-tax',
  'rectangle-group': 'template',
  'rectangle-stack': 'collection',
  'server-stack': 'server',
  'signal': 'status-online',
  'signal-slash': 'status-offline',
  'speaker-wave': 'volume-up',
  'speaker-x-mark': 'volume-off',
  'squares-2x2': 'view-grid',
  'squares-plus': 'view-grid-add',
  'swatch': 'color-swatch',
  'table-cells': 'table',
  'user-minus': 'user-remove',
  'user-plus': 'user-add',
  'view-columns': 'view-boards',
  'x-mark': 'x',
}

function registered(name) {
  const componentName = `HeroiconsOutline${upperFirst(camelCase(name))}`

  return app?.component(componentName) ? componentName : null
}

/**
 * The global component name that draws a Heroicons name, or null when Nova has none.
 */
export function heroiconComponent(name) {
  if (!name) {
    return null
  }

  const found = registered(name) || registered(V2_TO_V1[name])

  if (!found && Nova.config('debug')) {
    console.warn(`[nova-action-toolbar] Nova has no Heroicon "${name}". Use a raw <svg> instead.`)
  }

  return found
}
