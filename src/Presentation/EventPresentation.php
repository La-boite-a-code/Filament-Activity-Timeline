<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Presentation;

/**
 * A globally registered presentation for a single event name, custom or not.
 * Used to give business events such as "invoice.sent" a label, an icon, a color
 * and a sentence template shared across every timeline.
 */
final class EventPresentation
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected ?string $color = null;

    protected ?string $sentence = null;

    /**
     * @var array<string, string>
     */
    protected array $propertyBindings = [];

    public function __construct(
        protected string $key,
    ) {}

    public static function make(string $key): static
    {
        return new self($key);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function sentence(string $template): static
    {
        $this->sentence = $template;

        return $this;
    }

    /**
     * Bind a sentence variable name to a property path found on the activity,
     * so ":recipient" can read from "recipient_email".
     */
    public function property(string $name, string $path): static
    {
        $this->propertyBindings[$name] = $path;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getSentence(): ?string
    {
        return $this->sentence;
    }

    /**
     * @return array<string, string>
     */
    public function getPropertyBindings(): array
    {
        return $this->propertyBindings;
    }
}
