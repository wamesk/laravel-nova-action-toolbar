<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

use JsonSerializable;

/**
 * One of Nova's built-in controls (preview, replicate, view, edit, delete, …).
 *
 * Without settings a control stays where Nova draws it today. A setting moves it — and
 * only there: a control shown as a button is no longer in ⋯, and the other way round.
 * Who may use a control is still decided by Nova (authorizedToView, authorizedToDelete, …).
 *
 *     public static function toolbarControls(): array
 *     {
 *         return [
 *             Control::delete()->showInMenu(on: 'inline'),
 *             Control::replicate()->showAsButton(on: 'detail')->withIcon('document-duplicate'),
 *         ];
 *     }
 */
final class Control extends ToolbarSettings implements JsonSerializable
{
    public const PREVIEW = 'preview';

    public const REPLICATE = 'replicate';

    public const VIEW = 'view';

    public const EDIT = 'edit';

    public const DELETE = 'delete';

    public const RESTORE = 'restore';

    public const FORCE_DELETE = 'forceDelete';

    public const IMPERSONATE = 'impersonate';

    private string $name = '';

    public static function preview(): self
    {
        return self::named(self::PREVIEW);
    }

    public static function replicate(): self
    {
        return self::named(self::REPLICATE);
    }

    public static function view(): self
    {
        return self::named(self::VIEW);
    }

    public static function edit(): self
    {
        return self::named(self::EDIT);
    }

    public static function delete(): self
    {
        return self::named(self::DELETE);
    }

    public static function restore(): self
    {
        return self::named(self::RESTORE);
    }

    public static function forceDelete(): self
    {
        return self::named(self::FORCE_DELETE);
    }

    public static function impersonate(): self
    {
        return self::named(self::IMPERSONATE);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return ['control' => $this->name] + $this->toArray();
    }

    private static function named(string $name): self
    {
        $control = new self;
        $control->name = $name;

        return $control;
    }
}
