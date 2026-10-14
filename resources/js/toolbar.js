import ActionDropdown from './components/ActionDropdown.vue'
import ActionSelector from './components/ActionSelector.vue'
import CreateResourceButton from './components/CreateResourceButton.vue'
import DetailActionDropdown from './components/DetailActionDropdown.vue'
import InlineActionDropdown from './components/InlineActionDropdown.vue'

/**
 * Nova registers its own components after the booting callbacks and skips a name that
 * is already taken, so these replacements win without touching Nova's files.
 */
Nova.booting(app => {
  app.component('ActionDropdown', ActionDropdown)
  app.component('ActionSelector', ActionSelector)
  app.component('CreateResourceButton', CreateResourceButton)
  app.component('DetailActionDropdown', DetailActionDropdown)
  app.component('InlineActionDropdown', InlineActionDropdown)
})
