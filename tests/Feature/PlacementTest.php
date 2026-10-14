<?php

declare(strict_types=1);

use Wame\LaravelNovaActionToolbar\Context;
use Wame\LaravelNovaActionToolbar\Tests\Support\PlainAction;

/**
 * Where an action shows up: a button, an icon or the ⋯ menu, per context. The `on:` argument
 * picks the contexts; leaving it out means all three.
 */
it('shows an action as a button in every context by default', function () {
    expect(toolbarOf((new PlainAction)->showAsButton()))->toBe([
        'display' => ['index' => 'button', 'inline' => 'button', 'detail' => 'button'],
    ]);
});

it('shows an action as a button only in the contexts it names', function (string|array|Context $on, array $display) {
    expect(toolbarOf((new PlainAction)->showAsButton(on: $on)))->toBe(['display' => $display]);
})->with([
    'one context' => ['detail', ['detail' => 'button']],
    'a list' => [['index', 'inline'], ['index' => 'button', 'inline' => 'button']],
    'an enum case' => [Context::Inline, ['inline' => 'button']],
    'a mixed list' => [[Context::Index, 'detail'], ['index' => 'button', 'detail' => 'button']],
    'a repeated context' => [['detail', 'detail', Context::Detail], ['detail' => 'button']],
]);

it('refuses a context it does not know', function (string|array $on) {
    expect(fn () => (new PlainAction)->showAsButton(on: $on))
        ->toThrow(InvalidArgumentException::class, 'Unknown toolbar context');
})->with([
    'a typo' => 'indx',
    'a different case' => 'Detail',
    'one bad entry in a list' => [['index', 'row']],
]);

it('refuses an unknown context for every placement', function (string $method) {
    expect(fn () => (new PlainAction)->{$method}(on: 'table'))
        ->toThrow(InvalidArgumentException::class);
})->with(['showAsButton', 'showAsIcon', 'showInMenu']);

it('shows an action as an icon with its icon in one context', function () {
    expect(toolbarOf((new PlainAction)->showAsIcon('arrow-down-tray', on: 'inline')))->toBe([
        'display' => ['inline' => 'icon'],
        'icon' => ['type' => 'heroicon', 'name' => 'arrow-down-tray'],
    ]);
});

it('shows an action as an icon without setting an icon', function () {
    expect(toolbarOf((new PlainAction)->showAsIcon(on: 'detail')))->toBe([
        'display' => ['detail' => 'icon'],
    ]);
});

it('takes a style along with an icon placement', function () {
    expect(toolbarOf((new PlainAction)->showAsIcon('trash', on: 'inline', style: 'danger')))->toBe([
        'display' => ['inline' => 'icon'],
        'icon' => ['type' => 'heroicon', 'name' => 'trash'],
        'style' => 'danger',
    ]);
});

it('puts an action back into the menu only in the context it names', function () {
    $action = (new PlainAction)->showAsButton()->showInMenu(on: 'inline');

    expect(toolbarOf($action))->toBe([
        'display' => ['index' => 'button', 'inline' => 'menu', 'detail' => 'button'],
    ]);
});

it('lets the last placement of a context win', function () {
    $action = (new PlainAction)
        ->showAsButton(on: 'detail')
        ->showAsIcon(on: 'detail')
        ->showAsButton(on: 'index');

    expect(toolbarOf($action)['display'])->toBe(['detail' => 'icon', 'index' => 'button']);
});

it('resolves no context to every context', function () {
    expect(Context::resolve(null))->toBe([Context::Index, Context::Inline, Context::Detail]);
});
