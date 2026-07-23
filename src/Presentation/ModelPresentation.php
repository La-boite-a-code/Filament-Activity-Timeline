<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Presentation;

use Closure;

/**
 * The reusable, per model presentation. Declared once (in a service provider,
 * on the model itself, or locally on a timeline) and applied everywhere that
 * model appears in a timeline.
 */
final class ModelPresentation
{
    protected ?string $label = null;

    protected ?string $pluralLabel = null;

    protected ?Closure $recordTitleResolver = null;

    /**
     * @var list<string>
     */
    protected array $recordTitleAttributes = [];

    protected ?string $icon = null;

    protected ?string $color = null;

    protected ?string $resource = null;

    /**
     * @var array<string, AttributePresentation>
     */
    protected array $attributes = [];

    /**
     * @var array<string, string>
     */
    protected array $eventSentences = [];

    /**
     * @var array<string, Closure>
     */
    protected array $eventSentenceCallbacks = [];

    public static function make(): static
    {
        return new self;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function pluralLabel(string $label): static
    {
        $this->pluralLabel = $label;

        return $this;
    }

    public function recordTitle(Closure $callback): static
    {
        return $this->recordTitleUsing($callback);
    }

    public function recordTitleUsing(Closure $callback): static
    {
        $this->recordTitleResolver = $callback;

        return $this;
    }

    /**
     * @param  array<array-key, string>  $attributes
     */
    public function recordTitleAttributes(array $attributes): static
    {
        $this->recordTitleAttributes = array_values($attributes);

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

    public function resource(string $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @param  array<string, AttributePresentation>  $attributes
     */
    public function attributes(array $attributes): static
    {
        foreach ($attributes as $name => $attribute) {
            $this->attributes[$name] = $attribute;
        }

        return $this;
    }

    public function attribute(string $name, AttributePresentation $attribute): static
    {
        $this->attributes[$name] = $attribute;

        return $this;
    }

    /**
     * Terse way to label several attributes at once.
     *
     * @param  array<string, string>  $labels
     */
    public function attributeLabels(array $labels): static
    {
        foreach ($labels as $name => $label) {
            $this->attribute($name, $this->ensureAttribute($name)->label($label));
        }

        return $this;
    }

    public function attributeLabel(string $name, string $label): static
    {
        $this->ensureAttribute($name)->label($label);

        return $this;
    }

    public function formatAttributeUsing(string $name, Closure $callback): static
    {
        $this->ensureAttribute($name)->formatUsing($callback);

        return $this;
    }

    /**
     * @param  list<string>  $names
     */
    public function hiddenAttributes(array $names): static
    {
        foreach ($names as $name) {
            $this->ensureAttribute($name)->hidden();
        }

        return $this;
    }

    public function eventSentence(string $event, string $template): static
    {
        $this->eventSentences[$event] = $template;

        return $this;
    }

    public function eventSentenceUsing(string $event, Closure $callback): static
    {
        $this->eventSentenceCallbacks[$event] = $callback;

        return $this;
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getPluralLabel(): ?string
    {
        return $this->pluralLabel;
    }

    public function getRecordTitleResolver(): ?Closure
    {
        return $this->recordTitleResolver;
    }

    /**
     * @return list<string>
     */
    public function getRecordTitleAttributes(): array
    {
        return $this->recordTitleAttributes;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function getAttribute(string $name): ?AttributePresentation
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return array<string, AttributePresentation>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getEventSentence(string $event): ?string
    {
        return $this->eventSentences[$event] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getEventSentences(): array
    {
        return $this->eventSentences;
    }

    public function getEventSentenceCallback(string $event): ?Closure
    {
        return $this->eventSentenceCallbacks[$event] ?? null;
    }

    /**
     * Merge another presentation on top of this one. Values defined on the
     * other presentation win, which is what powers the local override rules.
     */
    public function mergeOnTopOf(ModelPresentation $base): static
    {
        $merged = clone $base;

        $merged->label = $this->label ?? $base->label;
        $merged->pluralLabel = $this->pluralLabel ?? $base->pluralLabel;
        $merged->recordTitleResolver = $this->recordTitleResolver ?? $base->recordTitleResolver;
        $merged->recordTitleAttributes = $this->recordTitleAttributes ?: $base->recordTitleAttributes;
        $merged->icon = $this->icon ?? $base->icon;
        $merged->color = $this->color ?? $base->color;
        $merged->resource = $this->resource ?? $base->resource;
        $merged->attributes = [...$base->attributes, ...$this->attributes];
        $merged->eventSentences = [...$base->eventSentences, ...$this->eventSentences];
        $merged->eventSentenceCallbacks = [...$base->eventSentenceCallbacks, ...$this->eventSentenceCallbacks];

        return $merged;
    }

    protected function ensureAttribute(string $name): AttributePresentation
    {
        return $this->attributes[$name] ??= AttributePresentation::make();
    }
}
