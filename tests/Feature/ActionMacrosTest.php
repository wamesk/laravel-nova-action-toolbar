<?php

declare(strict_types=1);

use Laravel\Nova\Actions\Action;
use Wame\LaravelNovaActionToolbar\ActionToolbarServiceProvider;
use Wame\LaravelNovaActionToolbar\Tests\Support\PlainAction;

/**
 * The macros are the package's whole PHP surface on a Nova action. Everything they set lands
 * in the action's meta under "toolbar", and an action that never calls one must reach the
 * browser exactly as Nova serializes it today — that is what keeps every untouched action in ⋯.
 */
it('registers every toolbar method as a macro on Nova actions', function (string $method) {
    expect(Action::hasMacro($method))->toBeTrue();
})->with(ActionToolbarServiceProvider::ACTION_METHODS);

it('keeps the first registration when the macros are registered again', function () {
    $macros = new ReflectionProperty(Action::class, 'macros');
    $before = $macros->getValue();

    ActionToolbarServiceProvider::registerActionMacros();

    foreach (ActionToolbarServiceProvider::ACTION_METHODS as $method) {
        expect($macros->getValue()[$method])->toBe($before[$method]);
    }
});

it('serializes an action without a toolbar call without a toolbar key', function () {
    $action = (new PlainAction)->withMeta(['custom' => 'kept']);

    expect($action->meta())->toBe(['custom' => 'kept'])
        ->and($action->jsonSerialize())->not->toHaveKey('toolbar')
        ->and($action->jsonSerialize()['custom'])->toBe('kept');
});

it('serializes the toolbar settings into the action json', function () {
    $json = (new PlainAction)->showAsButton()->jsonSerialize();

    expect($json['toolbar'])->toBe([
        'display' => ['index' => 'button', 'inline' => 'button', 'detail' => 'button'],
    ]);
});

it('leaves the rest of the meta untouched', function () {
    $action = (new PlainAction)->withMeta(['custom' => 'kept'])->showAsButton(on: 'detail');

    expect($action->meta()['custom'])->toBe('kept')
        ->and(toolbarOf($action))->toBe(['display' => ['detail' => 'button']]);
});

it('returns the action itself so the chain goes on', function () {
    $action = new PlainAction;

    expect($action->showAsButton())->toBe($action)
        ->and($action->onlyOnDetail()->withIcon('document-plus'))->toBe($action);
});

it('accumulates settings across chained calls', function () {
    $action = (new PlainAction)
        ->showAsButton()
        ->withIcon('document-plus')
        ->buttonStyle('primary')
        ->priority(10);

    expect(toolbarOf($action))->toBe([
        'display' => ['index' => 'button', 'inline' => 'button', 'detail' => 'button'],
        'icon' => ['type' => 'heroicon', 'name' => 'document-plus'],
        'style' => 'primary',
        'priority' => 10,
    ]);
});

it('passes named arguments through the macro', function () {
    $action = (new PlainAction)->showAsButton(style: 'outline');

    expect(toolbarOf($action))->toBe([
        'display' => ['index' => 'button', 'inline' => 'button', 'detail' => 'button'],
        'style' => 'outline',
    ]);
});

it('mixes a positional and a named argument through the macro', function () {
    $action = (new PlainAction)->showAsIcon('arrow-down-tray', on: 'inline');

    expect(toolbarOf($action))->toBe([
        'display' => ['inline' => 'icon'],
        'icon' => ['type' => 'heroicon', 'name' => 'arrow-down-tray'],
    ]);
});

it('stores the priority', function () {
    expect(toolbarOf((new PlainAction)->priority(10)))->toBe(['priority' => 10]);
});

it('leaves the action as it was when a setting is rejected', function () {
    $action = (new PlainAction)->showAsButton(on: 'detail');

    expect(fn () => $action->buttonStyle('neon'))->toThrow(InvalidArgumentException::class)
        ->and(toolbarOf($action))->toBe(['display' => ['detail' => 'button']]);
});

it('keeps the settings of one action off another', function () {
    $first = (new PlainAction)->showAsButton();
    $second = new PlainAction;

    expect(toolbarOf($first))->not->toBeNull()
        ->and(toolbarOf($second))->toBeNull();
});
