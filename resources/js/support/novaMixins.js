/**
 * Stand-in for Nova's `@/mixins` index. Nova's useActions imports only `Errors` from it;
 * the full index would pull every Nova mixin into this bundle.
 */
// The Errors class alone — the package entry also bundles axios for its Form class.
export { default as Errors } from 'form-backend-validation/src/Errors'
