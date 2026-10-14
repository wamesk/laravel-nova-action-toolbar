<?php

declare(strict_types=1);

use Wame\LaravelNovaActionToolbar\Icon;
use Wame\LaravelNovaActionToolbar\Tests\Support\PlainAction;

/**
 * An icon is a Heroicons name or a raw <svg>. The browser inserts the SVG as markup, so it is
 * reduced to drawing elements and attributes: whatever can run code, load something or carry
 * a style has to be gone before the action reaches the page.
 */
it('stores a Heroicons name', function () {
    expect(toolbarOf((new PlainAction)->withIcon('document-plus')))->toBe([
        'icon' => ['type' => 'heroicon', 'name' => 'document-plus'],
    ]);
});

it('trims the icon name', function () {
    expect(Icon::make('  arrow-down-tray '))->toBe(['type' => 'heroicon', 'name' => 'arrow-down-tray']);
});

it('refuses a name that is neither a Heroicons name nor an svg', function (string $icon) {
    expect(fn () => (new PlainAction)->withIcon($icon))
        ->toThrow(InvalidArgumentException::class, 'is neither a Heroicons name');
})->with([
    'spaces and punctuation' => 'Bad Name!',
    'upper case' => 'DocumentPlus',
    'a trailing dash' => 'document-',
    'a double dash' => 'document--plus',
    'nothing' => '',
    'another element' => '<div>x</div>',
    'a doctype before the svg' => '<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>',
]);

it('stores a raw svg as svg markup', function () {
    $icon = toolbarOf((new PlainAction)->withIcon(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 4v16m8-8H4"/></svg>'
    ))['icon'];

    expect($icon)->toBe([
        'type' => 'svg',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 4v16m8-8H4"/></svg>',
    ]);
});

it('strips whatever can run code, load something or carry a style from an svg', function (string $inner, string $forbidden) {
    $svg = Icon::sanitize(
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24">'.$inner.'</svg>'
    );

    expect(strtolower($svg))->not->toContain($forbidden);
})->with([
    'a script element' => ['<script>alert(1)</script><path d="M0 0"/>', 'script'],
    'an upper-case script element' => ['<SCRIPT>alert(1)</SCRIPT><path d="M0 0"/>', 'alert'],
    'an event handler' => ['<path d="M0 0" onload="alert(1)"/>', 'onload'],
    'a mixed-case event handler' => ['<path d="M0 0" onMouseOver="alert(1)"/>', 'alert'],
    'a link element' => ['<a href="https://evil.test"><path d="M0 0"/></a>', 'evil'],
    'an href attribute' => ['<path d="M0 0" href="https://evil.test"/>', 'href'],
    'an xlink href attribute' => ['<path d="M0 0" xlink:href="https://evil.test"/>', 'evil'],
    'a style attribute' => ['<path d="M0 0" style="fill:red"/>', 'style'],
    'a style element' => ['<style>path{fill:red}</style><path d="M0 0"/>', 'style'],
    'a use element' => ['<use href="#shape"/><path d="M0 0"/>', 'use'],
    'a foreign object' => ['<foreignObject><div>x</div></foreignObject><path d="M0 0"/>', 'foreignobject'],
    'an image' => ['<image href="https://evil.test/x.png"/><path d="M0 0"/>', 'image'],
    'a javascript url in an allowed attribute' => ['<path d="M0 0" fill="javascript:alert(1)"/>', 'javascript'],
    'a remote url in an allowed attribute' => ['<path d="M0 0" fill="url(http://evil.test/x)"/>', 'evil'],
    'a remote url in a mask' => ['<path d="M0 0" mask="url(https://evil.test/m.svg#m)"/>', 'evil'],
    'a comment' => ['<!-- secret --><path d="M0 0"/>', 'secret'],
    'a cdata section' => ['<![CDATA[alert(1)]]><path d="M0 0"/>', 'alert'],
    'stray text' => ['hello<path d="M0 0"/>', 'hello'],
]);

it('keeps the drawing of an svg', function () {
    $svg = Icon::sanitize(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" onload="alert(1)">'
        .'<title>Download</title>'
        .'<defs><linearGradient id="grad"><stop offset="0" stop-color="#fff"/></linearGradient></defs>'
        .'<path d="M12 4v16" fill="url(#grad)" stroke="currentColor" stroke-width="1.5"/>'
        .'<circle cx="12" cy="12" r="3" clip-path="url(#clip)"/>'
        .'</svg>'
    );

    expect($svg)
        ->toContain('viewBox="0 0 24 24"')
        ->toContain('<title>Download</title>')
        ->toContain('<linearGradient id="grad"><stop offset="0" stop-color="#fff"/></linearGradient>')
        ->toContain('<path d="M12 4v16" fill="url(#grad)" stroke="currentColor" stroke-width="1.5"/>')
        ->toContain('<circle cx="12" cy="12" r="3" clip-path="url(#clip)"/>')
        ->not->toContain('onload');
});

it('refuses an svg string that is not a well-formed svg element', function (string $svg) {
    expect(fn () => (new PlainAction)->withIcon($svg))
        ->toThrow(InvalidArgumentException::class, 'not a well-formed <svg>');
})->with([
    'an unclosed element' => '<svg><path d="M0 0"></svg>',
    'an unclosed root' => '<svg viewBox="0 0 24 24">',
    'a different root that starts like svg' => '<svgx></svgx>',
    'markup after the root' => '<svg></svg><script>alert(1)</script>',
    'an undeclared entity' => '<svg>&x;</svg>',
]);
