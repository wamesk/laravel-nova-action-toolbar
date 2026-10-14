import { computed, ref } from 'vue'
import { useLocalization } from '@/composables/useLocalization'

/**
 * Responses Nova's axios already reacts to on its own (login, the 403 page, the
 * expired-token dialog). Opening a form on top of that would only get in the way.
 */
const HANDLED_BY_NOVA = [401, 403, 419]

/**
 * Where Nova lists the actions that belong to the endpoint an action posts to:
 * `/nova-api/{resource}/action` → `/nova-api/{resource}/actions`, and the same for a
 * lens (`…/lens/{lens}/action`). An endpoint of any other shape is someone else's, and
 * its actions are not looked up.
 */
export function actionsEndpointFor(endpoint, resourceName) {
  if (!endpoint) {
    return `/nova-api/${resourceName}/actions`
  }

  return /\/action\/?$/.test(endpoint) ? endpoint.replace(/\/action\/?$/, '/actions') : null
}

/**
 * The form of a table row's action, loaded for that row before it opens.
 *
 * Nova bakes a row's actions — fields included — into the plain index response
 * (`Resource::serializeForIndex()`). That request names no record, so an action whose
 * fields depend on the record (select options, a pre-filled amount) arrives with empty
 * fields, and Nova's row menu never asks the server again. The detail page does not have
 * the problem: it loads its actions with `resourceId`.
 *
 * So before an action with fields opens from a row, this asks Nova's actions endpoint for
 * that one row with the parameters the detail page sends (plus the via-relationship ones
 * the index sends), and the modal opens with the answer. `display` is left out on purpose:
 * Nova then lists every action the user may see, so a row-only action (`onlyInline()`) is
 * found too. Actions without fields, and every place other than a row, run as before —
 * no extra request.
 *
 * When the request fails the baked action is used and the user is told; when the answer
 * does not list the action (e.g. a row-only action of a lens, whose endpoint lists index
 * actions only) the baked action is used silently, as before this package asked.
 */
export function useRowActionForm(props) {
  const { __ } = useLocalization()

  // uriKey → the action as Nova serialized it for the row.
  const loaded = ref({})
  const loadingKey = ref(null)

  const actions = computed(() => props.actions.map(action => loaded.value[action.uriKey] || action))

  const rowId = () =>
    Array.isArray(props.selectedResources) && props.selectedResources.length === 1
      ? props.selectedResources[0]
      : null

  /**
   * Only a row's action with fields, posting to one of Nova's own endpoints, is loaded.
   */
  const needsRowForm = action =>
    props.context === 'inline' &&
    rowId() !== null &&
    (action.fields?.length ?? 0) > 0 &&
    actionsEndpointFor(props.endpoint, props.resourceName) !== null

  // Object.assign, no spread: Babel would bundle a helper per module for it.
  const remember = (uriKey, action) => {
    const next = Object.assign({}, loaded.value)

    if (action) {
      next[uriKey] = action
    } else {
      delete next[uriKey]
    }

    loaded.value = next
  }

  /**
   * Load the action's form for the row (call it only when needsRowForm() says so).
   * Resolves to false when the form must not open (Nova is already handling the
   * response), true otherwise. Plain promises, no async/await: Babel would bundle its
   * generator runtime for it.
   */
  function load(action) {
    loadingKey.value = action.uriKey

    return Nova.request()
      .get(actionsEndpointFor(props.endpoint, props.resourceName), {
        params: {
          resourceId: rowId(),
          editing: true,
          editMode: 'create',
          viaResource: props.viaResource,
          viaResourceId: props.viaResourceId,
          viaRelationship: props.viaRelationship,
        },
      })
      .then(({ data }) => {
        const fresh = (data?.actions || []).find(candidate => candidate.uriKey === action.uriKey)

        remember(action.uriKey, fresh)

        return true
      })
      .catch(error => {
        remember(action.uriKey, null)

        if (HANDLED_BY_NOVA.includes(error?.response?.status)) {
          return false
        }

        Nova.error(__('The form of this action could not be loaded for this row.'))

        return true
      })
      .finally(() => {
        loadingKey.value = null
      })
  }

  return { actions, loadingKey, needsRowForm, load }
}
