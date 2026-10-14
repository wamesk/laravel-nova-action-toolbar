<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

/**
 * How an action or a built-in control is shown in one context.
 */
enum Display: string
{
    /** A button with the label (and the icon, when one is set). */
    case Button = 'button';

    /** An icon-only button; the label becomes its tooltip and accessible name. */
    case Icon = 'icon';

    /** An item in the ⋯ menu — where Nova puts every action today. */
    case Menu = 'menu';
}
