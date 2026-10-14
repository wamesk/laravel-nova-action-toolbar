<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar\Tests\Support;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * A Nova resource that leaves every built-in control where Nova draws it.
 */
class Customer extends Resource
{
    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
