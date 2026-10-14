<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Wame\LaravelNovaActionToolbar\ActionToolbarServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Only the package's own provider. Nova's provider is not needed: the macros live on
     * Laravel\Nova\Actions\Action itself, and Action::jsonSerialize() only resolves a
     * NovaRequest from the container, which any Laravel application can do.
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ActionToolbarServiceProvider::class];
    }

    /**
     * An in-memory SQLite database keeps the suite runnable with no setup at all — no server,
     * no credentials, no leftover state between tests.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
