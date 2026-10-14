<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

/**
 * An icon is either a Heroicons name or a raw `<svg>` string.
 *
 * The browser inserts a raw SVG as markup, so it is reduced to a whitelist of
 * drawing elements and attributes first: no scripts, no event handlers, no links,
 * no inline styles. Treat it as code anyway and never build it from user input.
 */
final class Icon
{
    /** @var list<string> */
    private const ELEMENTS = [
        'svg', 'g', 'path', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'rect', 'title', 'defs',
        'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask',
    ];

    /** @var list<string> */
    private const ATTRIBUTES = [
        'viewbox', 'xmlns', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'fill-opacity',
        'fill-rule', 'clip-rule', 'clip-path', 'mask', 'opacity', 'd', 'cx', 'cy', 'r', 'rx', 'ry',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'width', 'height', 'points', 'transform', 'id', 'offset',
        'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform',
    ];

    /**
     * @return array{type: 'heroicon', name: string}|array{type: 'svg', svg: string}
     */
    public static function make(string $icon): array
    {
        $icon = trim($icon);

        if (str_starts_with(strtolower($icon), '<svg')) {
            return ['type' => 'svg', 'svg' => self::sanitize($icon)];
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $icon) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Icon "%s" is neither a Heroicons name (e.g. "arrow-down-tray") nor an <svg> string.',
                $icon,
            ));
        }

        return ['type' => 'heroicon', 'name' => $icon];
    }

    public static function sanitize(string $svg): string
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;

        if (! $loaded || ! $root instanceof DOMElement || strtolower($root->localName) !== 'svg') {
            throw new InvalidArgumentException('The icon is not a well-formed <svg> element.');
        }

        self::clean($root);

        return (string) $document->saveXML($root);
    }

    private static function clean(DOMElement $element): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = strtolower($attribute->nodeValue ?? '');

            $allowed = in_array($name, self::ATTRIBUTES, true)
                && ! str_contains($value, 'javascript:')
                && (! str_contains($value, 'url(') || preg_match('/^url\(#[\w-]+\)$/', trim($value)) === 1);

            if (! $allowed) {
                $element->removeAttributeNode($attribute);
            }
        }

        /** @var list<DOMNode> $children */
        $children = iterator_to_array($element->childNodes);

        foreach ($children as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->localName), self::ELEMENTS, true)) {
                self::clean($child);

                continue;
            }

            if ($child->nodeType === XML_TEXT_NODE && $element->localName === 'title') {
                continue;
            }

            if ($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue ?? '') === '') {
                continue;
            }

            $element->removeChild($child);
        }
    }
}
