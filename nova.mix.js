const mix = require('laravel-mix')
const path = require('path')

const fs = require('fs')

/**
 * Builds against an installed Nova source.
 *
 * `@` points at <nova>/resources/js, so the toolbar reuses Nova's own action runner
 * (useActions), its delete/restore mixins and the original components it wraps —
 * compiled from that Nova version. Rebuild after a Nova upgrade.
 *
 * Nova is looked up in NOVA_PATH, then in this package's own vendor/ (composer install),
 * then in the vendor/ of an application that holds the package in a sub-folder.
 */
const novaPath = [
  process.env.NOVA_PATH,
  path.join(__dirname, 'vendor/laravel/nova'),
  path.join(__dirname, '../../vendor/laravel/nova'),
  path.join(__dirname, '../../laravel/nova'),
].find(candidate => candidate && fs.existsSync(path.join(candidate, 'resources/js')))

if (!novaPath) {
  throw new Error('Laravel Nova source not found. Run `composer install` or set NOVA_PATH.')
}

class NovaExtension {
  name() {
    return 'nova-extension'
  }

  register(name) {
    this.name = name
  }

  webpackConfig(webpackConfig) {
    webpackConfig.externals = {
      vue: 'Vue',
    }

    webpackConfig.resolve.alias = {
      ...(webpackConfig.resolve.alias || {}),
      // Exact match first: Nova's useActions needs only `Errors` from the mixins index.
      '@/mixins$': path.join(__dirname, 'resources/js/support/novaMixins.js'),
      '@': path.join(novaPath, 'resources/js'),
      'laravel-nova': path.join(novaPath, 'resources/js/mixins/packages.js'),
    }

    // Nova's source files import lodash, vuex and form-backend-validation; resolve them
    // from this package's node_modules, not from vendor/laravel/nova (it ships none).
    webpackConfig.resolve.modules = [
      path.join(__dirname, 'node_modules'),
      'node_modules',
    ]

    webpackConfig.output = {
      ...(webpackConfig.output || {}),
      uniqueName: this.name,
    }
  }
}

mix.extend('nova', new NovaExtension())
