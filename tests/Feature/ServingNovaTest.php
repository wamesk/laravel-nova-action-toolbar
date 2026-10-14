<?php

declare(strict_types=1);

use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Wame\LaravelNovaActionToolbar\Tests\Support\Invoice;

/**
 * The assets, the translations and the script variables are handed to Nova only while it
 * serves a page. Nova keeps all of it in static properties, so every test puts back what it
 * found.
 */
beforeEach(function () {
    $this->novaState = [
        'resources' => Nova::$resources,
        'scripts' => Nova::$scripts,
        'styles' => Nova::$styles,
        'translations' => Nova::$translations,
        'jsonVariables' => Nova::$jsonVariables,
    ];

    Nova::$scripts = [];
    Nova::$styles = [];
    Nova::$jsonVariables = [];
});

afterEach(function () {
    Nova::$resources = $this->novaState['resources'];
    Nova::$scripts = $this->novaState['scripts'];
    Nova::$styles = $this->novaState['styles'];
    Nova::$translations = $this->novaState['translations'];
    Nova::$jsonVariables = $this->novaState['jsonVariables'];
});

it('registers nothing with Nova before Nova serves a page', function () {
    expect(Nova::$scripts)->toBe([])
        ->and(Nova::$styles)->toBe([])
        ->and(Nova::$jsonVariables)->not->toHaveKey('actionToolbar');
});

it('registers the script, the style and the translations when Nova serves a page', function () {
    event(new ServingNova(app(), request()));

    expect(collect(Nova::$scripts)->map->name()->all())->toContain('nova-action-toolbar')
        ->and(collect(Nova::$styles)->map->name()->all())->toContain('nova-action-toolbar')
        ->and(Nova::$translations)->toHaveKey('More actions');
});

it('hands the browser the layout and the resource controls', function () {
    Nova::$resources = [Invoice::class];

    event(new ServingNova(app(), request()));

    $variables = (Nova::$jsonVariables['actionToolbar'])();

    expect($variables['layout'])->toBe('grouped')
        ->and($variables['controls'])->toHaveKey('invoices');
});

it('reads the layout from the config and falls back to grouped', function (?string $configured, string $layout) {
    config(['nova-action-toolbar.layout' => $configured]);

    event(new ServingNova(app(), request()));

    expect((Nova::$jsonVariables['actionToolbar'])()['layout'])->toBe($layout);
})->with([
    'separate' => ['separate', 'separate'],
    'grouped' => ['grouped', 'grouped'],
    'an unknown layout' => ['columns', 'grouped'],
    'nothing' => [null, 'grouped'],
]);

it('ships grouped as the default layout', function () {
    expect(config('nova-action-toolbar.layout'))->toBe('grouped');
});
