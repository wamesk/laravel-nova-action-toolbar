<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

use InvalidArgumentException;

/**
 * Visual style of a button. Every style has a light and a dark variant.
 */
enum Style: string
{
    case Default = 'default';
    case Primary = 'primary';
    case Outline = 'outline';
    case Danger = 'danger';
    case Ghost = 'ghost';

    /** Developer colours from `buttonColors()`. */
    case Custom = 'custom';

    public static function resolve(string|self $style): self
    {
        if ($style instanceof self) {
            return $style;
        }

        return self::tryFrom($style) ?? throw new InvalidArgumentException(sprintf(
            'Unknown button style "%s". Use one of: %s.',
            $style,
            implode(', ', array_map(fn (self $case): string => $case->value, self::cases())),
        ));
    }
}
