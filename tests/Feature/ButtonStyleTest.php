<?php

declare(strict_types=1);

use Wame\LaravelNovaActionToolbar\Style;
use Wame\LaravelNovaActionToolbar\Tests\Support\PlainAction;

/**
 * A button's look: one of the named styles, or the developer's own colours. A colour ends up
 * in an inline `style` attribute in the browser, so only colour syntax may get through.
 */
it('sets a named button style', function (string $style) {
    expect(toolbarOf((new PlainAction)->buttonStyle($style)))->toBe(['style' => $style]);
})->with(['default', 'primary', 'outline', 'danger', 'ghost']);

it('takes the style enum as well as its name', function () {
    expect(toolbarOf((new PlainAction)->buttonStyle(Style::Danger)))->toBe(['style' => 'danger']);
});

it('refuses a style it does not know', function () {
    expect(fn () => (new PlainAction)->buttonStyle('neon'))
        ->toThrow(InvalidArgumentException::class, 'Unknown button style "neon"');
});

it('switches to the custom style and nests the light and dark colours', function () {
    $action = (new PlainAction)->buttonColors(
        text: '#fff',
        background: 'var(--colors-primary-500)',
        border: 'rgb(0, 0, 0)',
        dark: ['text' => '#000', 'background' => 'hsl(210 40% 20%)'],
    );

    expect(toolbarOf($action))->toBe([
        'style' => 'custom',
        'colors' => [
            'light' => ['text' => '#fff', 'background' => 'var(--colors-primary-500)', 'border' => 'rgb(0, 0, 0)'],
            'dark' => ['text' => '#000', 'background' => 'hsl(210 40% 20%)'],
        ],
    ]);
});

it('leaves out the dark colours and the empty keys when none are given', function () {
    expect(toolbarOf((new PlainAction)->buttonColors(background: 'red', border: '')))->toBe([
        'style' => 'custom',
        'colors' => ['light' => ['background' => 'red']],
    ]);
});

it('accepts colour syntax', function (string $colour) {
    expect(toolbarOf((new PlainAction)->buttonColors(text: $colour))['colors']['light']['text'])->toBe($colour);
})->with([
    'short hex' => '#fff',
    'long hex with alpha' => '#11223344',
    'a named colour' => 'rebeccapurple',
    'rgb' => 'rgb(10, 20, 30)',
    'rgba' => 'rgba(10, 20, 30, 0.5)',
    'modern rgb' => 'rgb(10 20 30 / 50%)',
    'hsl' => 'hsl(120deg 50% 50%)',
    'a Nova variable' => 'var(--colors-primary-500)',
    'a variable with a fallback' => 'var(--brand, #fff)',
]);

it('refuses anything that could close the style declaration', function (string $colour) {
    expect(fn () => (new PlainAction)->buttonColors(text: $colour))
        ->toThrow(InvalidArgumentException::class, 'is not a CSS colour');
})->with([
    'a declaration breakout' => 'red;}body{x',
    'a second declaration' => 'red; background-image: url(x)',
    'a remote url' => 'url(https://evil.test/x.png)',
    'a javascript url' => 'url(javascript:alert(1))',
    'a quote' => 'red"',
    'an angle bracket' => '</style><script>',
    'an escape' => '\\72 ed',
]);

it('checks the dark colours as strictly as the light ones', function () {
    expect(fn () => (new PlainAction)->buttonColors(text: '#fff', dark: ['text' => 'red;}body{x']))
        ->toThrow(InvalidArgumentException::class, 'is not a CSS colour');
});

it('refuses a dark colour key it does not know', function () {
    expect(fn () => (new PlainAction)->buttonColors(text: '#fff', dark: ['shadow' => '#000']))
        ->toThrow(InvalidArgumentException::class, 'Unknown colour key "shadow"');
});

it('refuses custom colours with no light colour at all', function (array $arguments) {
    expect(fn () => (new PlainAction)->buttonColors(...$arguments))
        ->toThrow(InvalidArgumentException::class, 'needs at least one colour');
})->with([
    'nothing' => [[]],
    'only empty strings' => [['text' => '', 'background' => '']],
    'only dark colours' => [['dark' => ['text' => '#000']]],
]);
