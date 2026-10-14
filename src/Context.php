<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

use InvalidArgumentException;

/**
 * The three places where Nova shows actions.
 */
enum Context: string
{
    /** Above the table on a resource index (standalone and bulk actions). */
    case Index = 'index';

    /** In a table row, next to Nova's view / edit / delete icons. */
    case Inline = 'inline';

    /** In the header of a resource detail. */
    case Detail = 'detail';

    /**
     * Turn the `on:` argument into a list of contexts. `null` means every context.
     *
     * @param  string|self|list<string|self>|null  $on
     * @return list<self>
     */
    public static function resolve(string|self|array|null $on): array
    {
        if ($on === null) {
            return self::cases();
        }

        $contexts = [];

        // Not `(array) $on`: casting an enum case yields its properties (name, value).
        foreach (is_array($on) ? $on : [$on] as $context) {
            if ($context instanceof self) {
                $contexts[] = $context;

                continue;
            }

            $contexts[] = self::tryFrom((string) $context) ?? throw new InvalidArgumentException(sprintf(
                'Unknown toolbar context "%s". Use one of: %s.',
                $context,
                implode(', ', array_map(fn (self $case): string => $case->value, self::cases())),
            ));
        }

        return array_values(array_unique($contexts, SORT_REGULAR));
    }
}
