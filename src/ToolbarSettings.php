<?php

declare(strict_types=1);

namespace Wame\LaravelNovaActionToolbar;

use InvalidArgumentException;

/**
 * How one action or one built-in control is placed and drawn.
 *
 * Only what the developer set is serialized. An action that never calls a toolbar
 * method carries no `toolbar` key at all, so Nova shows it exactly as before.
 *
 * @phpstan-type Colors array{text?: string, background?: string, border?: string}
 * @phpstan-type Serialized array{
 *     display?: array<string, string>,
 *     icon?: array{type: string, name?: string, svg?: string},
 *     style?: string,
 *     colors?: array{light: Colors, dark?: Colors},
 *     priority?: int,
 * }
 */
class ToolbarSettings
{
    /** @var array<string, string> context value => display value */
    protected array $display = [];

    /** @var array{type: string, name?: string, svg?: string}|null */
    protected ?array $icon = null;

    protected ?Style $style = null;

    /** @var array{light: Colors, dark?: Colors}|null */
    protected ?array $colors = null;

    protected ?int $priority = null;

    /**
     * @param  Serialized  $settings
     */
    public static function fromArray(array $settings): static
    {
        $instance = new static;
        $instance->display = $settings['display'] ?? [];
        $instance->icon = $settings['icon'] ?? null;
        $instance->style = isset($settings['style']) ? Style::from($settings['style']) : null;
        $instance->colors = $settings['colors'] ?? null;
        $instance->priority = $settings['priority'] ?? null;

        return $instance;
    }

    /**
     * Show as a button with its label (and its icon, when one is set).
     *
     * @param  string|Context|list<string|Context>|null  $on  'index', 'inline', 'detail'; null = everywhere
     */
    public function showAsButton(string|Context|array|null $on = null, string|Style|null $style = null): static
    {
        $this->place(Display::Button, $on);

        if ($style !== null) {
            $this->buttonStyle($style);
        }

        return $this;
    }

    /**
     * Show as an icon-only button. The label becomes its tooltip and accessible name.
     *
     * @param  string|Context|list<string|Context>|null  $on
     */
    public function showAsIcon(?string $icon = null, string|Context|array|null $on = null, string|Style|null $style = null): static
    {
        $this->place(Display::Icon, $on);

        if ($icon !== null) {
            $this->withIcon($icon);
        }

        if ($style !== null) {
            $this->buttonStyle($style);
        }

        return $this;
    }

    /**
     * Keep it in the ⋯ menu (the default for actions).
     *
     * @param  string|Context|list<string|Context>|null  $on
     */
    public function showInMenu(string|Context|array|null $on = null): static
    {
        return $this->place(Display::Menu, $on);
    }

    /**
     * A Heroicons name ("arrow-down-tray") or a raw "<svg …>" string.
     */
    public function withIcon(string $icon): static
    {
        $this->icon = Icon::make($icon);

        return $this;
    }

    public function buttonStyle(string|Style $style): static
    {
        $this->style = Style::resolve($style);

        return $this;
    }

    /**
     * Own text, background and border colours. Switches the style to "custom".
     *
     * @param  Colors  $dark  the same keys for the dark theme; a missing key falls back to the light value
     */
    public function buttonColors(?string $text = null, ?string $background = null, ?string $border = null, array $dark = []): static
    {
        $light = self::colors(['text' => $text, 'background' => $background, 'border' => $border]);

        if ($light === []) {
            throw new InvalidArgumentException('buttonColors() needs at least one colour.');
        }

        $this->colors = array_filter([
            'light' => $light,
            'dark' => self::colors($dark),
        ]);
        $this->style = Style::Custom;

        return $this;
    }

    /**
     * On a narrow screen buttons move into ⋯ from the last one. A higher priority stays visible longer.
     */
    public function priority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Serialized
     */
    public function toArray(): array
    {
        return array_filter([
            'display' => $this->display,
            'icon' => $this->icon,
            'style' => $this->style?->value,
            'colors' => $this->colors,
            'priority' => $this->priority,
        ], fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param  string|Context|list<string|Context>|null  $on
     */
    protected function place(Display $display, string|Context|array|null $on): static
    {
        foreach (Context::resolve($on) as $context) {
            $this->display[$context->value] = $display->value;
        }

        return $this;
    }

    /**
     * A colour lands in an inline `style` attribute, so only colour syntax is accepted:
     * hex, rgb()/hsl(), named colours and var(--…). Nothing that can close the declaration.
     *
     * @param  array<string, string|null>  $colors
     * @return Colors
     */
    private static function colors(array $colors): array
    {
        $clean = [];

        foreach ($colors as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (! in_array($key, ['text', 'background', 'border'], true)) {
                throw new InvalidArgumentException(sprintf('Unknown colour key "%s". Use text, background or border.', $key));
            }

            if (preg_match('/^[#a-zA-Z0-9(),.%\s\/-]+$/', $value) !== 1) {
                throw new InvalidArgumentException(sprintf('"%s" is not a CSS colour.', $value));
            }

            $clean[$key] = trim($value);
        }

        return $clean;
    }
}
