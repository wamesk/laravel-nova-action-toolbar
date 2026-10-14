<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar\Tests\Support;

use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Wame\LaravelNovaActionToolbar\Control;

/**
 * A Nova resource that moves two of Nova's built-in controls.
 */
class Invoice extends Resource
{
    /**
     * @return array<int, mixed>
     */
    public function fields(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Keyed on purpose: the provider has to hand the browser a list, not an object.
     *
     * @return array<string, Control>
     */
    public static function toolbarControls(): array
    {
        return [
            'delete' => Control::delete()->showInMenu(on: 'inline'),
            'replicate' => Control::replicate()->showAsButton(on: 'detail')->withIcon('document-duplicate'),
        ];
    }
}
