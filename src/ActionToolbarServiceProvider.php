<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class ActionToolbarServiceProvider extends ServiceProvider
{
    /**
     * The ToolbarSettings methods that every Nova action gets as a macro.
     *
     * @var list<string>
     */
    public const ACTION_METHODS = [
        'showAsButton',
        'showAsIcon',
        'showInMenu',
        'withIcon',
        'buttonStyle',
        'buttonColors',
        'priority',
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova-action-toolbar.php', 'nova-action-toolbar');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/nova-action-toolbar.php' => config_path('nova-action-toolbar.php'),
        ], 'nova-action-toolbar-config');

        self::registerActionMacros();

        Nova::serving(function (ServingNova $event): void {
            Nova::script('nova-action-toolbar', __DIR__.'/../dist/js/toolbar.js');
            Nova::style('nova-action-toolbar', __DIR__.'/../dist/css/toolbar.css');
            Nova::translations(__DIR__.'/../lang/'.app()->getLocale().'.json');

            // A closure: Nova evaluates it when the page renders, after every
            // service provider has registered its resources.
            Nova::provideToScript([
                'actionToolbar' => fn (): array => [
                    'layout' => config('nova-action-toolbar.layout') === 'separate' ? 'separate' : 'grouped',
                    'controls' => self::resourceControls(),
                ],
            ]);
        });
    }

    /**
     * Every setting is stored in the action's meta under "toolbar". Nova merges the
     * meta into the action's JSON, so the browser reads it as `action.toolbar`.
     */
    public static function registerActionMacros(): void
    {
        foreach (self::ACTION_METHODS as $method) {
            if (Action::hasMacro($method)) {
                continue;
            }

            Action::macro($method, function (mixed ...$arguments) use ($method): Action {
                /** @var Action $this */
                $settings = ToolbarSettings::fromArray($this->meta['toolbar'] ?? []);
                $settings->{$method}(...$arguments);

                return $this->withMeta(['toolbar' => $settings->toArray()]);
            });
        }
    }

    /**
     * Control settings of every registered resource that declares `toolbarControls()`,
     * keyed by the resource's URI key.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function resourceControls(): array
    {
        $controls = [];

        foreach (Nova::$resources as $resource) {
            if (! method_exists($resource, 'toolbarControls')) {
                continue;
            }

            $controls[$resource::uriKey()] = array_values(array_map(
                fn (Control $control): array => $control->jsonSerialize(),
                $resource::toolbarControls(),
            ));
        }

        return $controls;
    }
}
