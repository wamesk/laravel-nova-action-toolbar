<?php

declare(strict_types=1);

use Wame\LaravelNovaActionToolbar\Control;

/**
 * Nova's built-in controls take the same settings as an action. A control without settings
 * carries only its name, which tells the browser to leave it where Nova draws it.
 */
it('serializes a moved control with its name first', function () {
    expect(Control::delete()->showInMenu(on: 'inline')->jsonSerialize())->toBe([
        'control' => 'delete',
        'display' => ['inline' => 'menu'],
    ]);
});

it('names every built-in control the way Nova does', function (Closure $make, string $name) {
    $control = $make();

    expect($control->name())->toBe($name)
        ->and($control->jsonSerialize())->toBe(['control' => $name]);
})->with([
    'preview' => [fn (): Control => Control::preview(), 'preview'],
    'replicate' => [fn (): Control => Control::replicate(), 'replicate'],
    'view' => [fn (): Control => Control::view(), 'view'],
    'edit' => [fn (): Control => Control::edit(), 'edit'],
    'delete' => [fn (): Control => Control::delete(), 'delete'],
    'restore' => [fn (): Control => Control::restore(), 'restore'],
    'force delete' => [fn (): Control => Control::forceDelete(), 'forceDelete'],
    'impersonate' => [fn (): Control => Control::impersonate(), 'impersonate'],
]);

it('takes every setting an action takes', function () {
    $control = Control::replicate()
        ->showAsButton(on: 'detail')
        ->withIcon('document-duplicate')
        ->buttonStyle('ghost')
        ->priority(5);

    expect($control->jsonSerialize())->toBe([
        'control' => 'replicate',
        'display' => ['detail' => 'button'],
        'icon' => ['type' => 'heroicon', 'name' => 'document-duplicate'],
        'style' => 'ghost',
        'priority' => 5,
    ]);
});

it('gives every call a control of its own', function () {
    $moved = Control::edit()->showAsIcon(on: 'inline');

    expect(Control::edit()->jsonSerialize())->toBe(['control' => 'edit'])
        ->and($moved->jsonSerialize())->toBe(['control' => 'edit', 'display' => ['inline' => 'icon']]);
});

it('encodes to the same json the browser reads', function () {
    expect(json_encode(Control::delete()->showInMenu(on: 'inline')))
        ->toBe('{"control":"delete","display":{"inline":"menu"}}');
});
