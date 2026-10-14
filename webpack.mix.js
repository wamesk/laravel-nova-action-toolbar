let mix = require('laravel-mix')

require('./nova.mix')

mix
  .setPublicPath('dist')
  .js('resources/js/toolbar.js', 'js')
  .vue({ version: 3 })
  .css('resources/css/toolbar.css', 'css')
  .nova('wamesk/laravel-nova-action-toolbar')
