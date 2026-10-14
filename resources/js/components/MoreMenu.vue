<template>
  <Dropdown class="nat-more-wrap">
    <template #default>
      <button
        type="button"
        class="nat-more"
        :class="{ 'nat-more--inline': inline }"
        :dusk="triggerDusk"
        :aria-label="__('More actions')"
        :aria-busy="busy ? 'true' : null"
        v-tooltip="__('Actions')"
        @click.stop
      >
        <span v-if="busy" class="nat-spinner" aria-hidden="true" />
        <svg v-else class="nat-more__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"
          />
        </svg>
      </button>
    </template>

    <template #menu>
      <DropdownMenu width="auto">
        <ScrollWrap :height="250">
          <nav class="px-1 divide-y divide-gray-100 dark:divide-gray-800 divide-solid">
            <!-- Buttons that did not fit on a narrow screen -->
            <div v-if="overflowItems.length > 0" class="py-1">
              <DropdownMenuItem
                v-for="item in overflowItems"
                :key="item.key"
                :as="item.href ? 'external' : 'button'"
                :href="item.href"
                :dusk="item.dusk ? `${item.dusk}-overflow` : null"
                :disabled="Boolean(item.disabled || item.disabledReason)"
                :title="item.label"
                class="border-none nat-menu-item"
                :class="{ 'nat-menu-item--danger': item.style === 'danger' }"
                @click="activate(item, $event)"
              >
                <ToolbarIcon v-if="item.icon" :icon="item.icon" class="nat-menu-item__icon" />
                <span class="nat-menu-item__text">
                  <span>{{ item.label }}</span>
                  <span v-if="item.disabledReason" class="nat-menu-item__reason">{{ item.disabledReason }}</span>
                </span>
              </DropdownMenuItem>
            </div>

            <slot name="menu" />

            <div v-for="section in filledSections" :key="section.key">
              <DropdownMenuHeading v-if="section.heading">{{ section.heading }}</DropdownMenuHeading>

              <div class="py-1">
                <DropdownMenuItem
                  v-for="item in section.items"
                  :key="item.key"
                  :as="item.href ? 'external' : 'button'"
                  :href="item.href"
                  :data-action-id="item.actionId"
                  :dusk="item.dusk"
                  :title="item.label"
                  :disabled="Boolean(item.disabled)"
                  class="border-none"
                  @click="item.onClick && item.onClick($event)"
                >
                  {{ item.label }}
                </DropdownMenuItem>
              </div>
            </div>
          </nav>
        </ScrollWrap>
      </DropdownMenu>
    </template>
  </Dropdown>
</template>

<script setup>
import { computed } from 'vue'
import ToolbarIcon from './ToolbarIcon.vue'

const emit = defineEmits(['activate'])

const props = defineProps({
  // [{ key, heading, items: [{ key, label, href, onClick, disabled, dusk, actionId }] }]
  sections: { type: Array, default: () => [] },
  // Pinned items hidden by the priority+ layout.
  overflowItems: { type: Array, default: () => [] },
  triggerDusk: { type: String, default: null },
  inline: { type: Boolean, default: false },
  // An action picked from this menu is loading its form.
  busy: { type: Boolean, default: false },
})

// A heading without items under it is not drawn (Nova does the same).
const filledSections = computed(() => props.sections.filter(section => section.items.length > 0))

function activate(item, event) {
  if (item.disabled || item.disabledReason) {
    event.preventDefault()
    return
  }

  if (!item.href) {
    emit('activate', item)
  }
}
</script>
