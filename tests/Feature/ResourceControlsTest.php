<?php

declare(strict_types=1);

use Laravel\Nova\Nova;
use Wame\LaravelNovaActionToolbar\ActionToolbarServiceProvider;
use Wame\LaravelNovaActionToolbar\Tests\Support\Customer;
use Wame\LaravelNovaActionToolbar\Tests\Support\Invoice;

/**
 * The browser learns about moved built-in controls from one map: resource URI key => the
 * controls that resource declares in `toolbarControls()`. Nova's resource list is static,
 * so every test puts back what it found.
 */
beforeEach(function () {
    $this->registeredResources = Nova::$resources;
});

afterEach(function () {
    Nova::$resources = $this->registeredResources;
});

it('collects the controls of every resource that declares them, keyed by uri key', function () {
    Nova::$resources = [Invoice::class, Customer::class];

    expect(ActionToolbarServiceProvider::resourceControls())->toBe([
        'invoices' => [
            ['control' => 'delete', 'display' => ['inline' => 'menu']],
            [
                'control' => 'replicate',
                'display' => ['detail' => 'button'],
                'icon' => ['type' => 'heroicon', 'name' => 'document-duplicate'],
            ],
        ],
    ]);
});

it('hands the browser a list even when a resource keys its controls', function () {
    Nova::$resources = [Invoice::class];

    expect(ActionToolbarServiceProvider::resourceControls()['invoices'])->toBeList();
});

it('returns nothing when no resource declares controls', function (array $resources) {
    Nova::$resources = $resources;

    expect(ActionToolbarServiceProvider::resourceControls())->toBe([]);
})->with([
    'no resources' => [[]],
    'a resource without controls' => [[Customer::class]],
]);
