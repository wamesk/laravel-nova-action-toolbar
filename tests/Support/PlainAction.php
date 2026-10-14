<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar\Tests\Support;

use Laravel\Nova\Actions\Action;

/**
 * A Nova action with nothing of its own: no fields, no handler, no meta.
 */
class PlainAction extends Action {}
