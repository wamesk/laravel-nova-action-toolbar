<?php

declare(strict_types=1);
use Composer\Autoload\ClassLoader;
use Laravel\Nova\Actions\Action;
use Wame\LaravelNovaActionToolbar\Tests\TestCase;

/**
 * Pest bootstrap for wamesk/laravel-nova-action-toolbar.
 *
 * Standalone (after `composer install` in the package):
 *
 *   ./vendor/bin/pest
 *
 * Inside an application that holds the package in wamesk/, from the application root:
 *
 *   ./vendor/bin/pest --test-directory=wamesk/laravel-nova-action-toolbar/tests \
 *                     -c wamesk/laravel-nova-action-toolbar/phpunit.xml
 *
 * Composer does not fold a dependency's autoload-dev into the application's autoloader,
 * so the test namespace is registered by hand. vendor/autoload.php memoizes its
 * ClassLoader and hands the same instance back on a second require.
 */
$loader = require __DIR__.'/bootstrap.php';

if ($loader instanceof ClassLoader) {
    $loader->addPsr4('Wame\\LaravelNovaActionToolbar\\Tests\\', __DIR__);
}

uses(TestCase::class)->in(__DIR__);

/**
 * What an action carries for the toolbar, or null when it carries nothing.
 *
 * @return array<string, mixed>|null
 */
function toolbarOf(Action $action): ?array
{
    return $action->meta()['toolbar'] ?? null;
}
