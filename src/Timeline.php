<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;

/**
 * The declarative, typed configuration for one timeline. It is built fresh on
 * every request (in a widget method or from the global registry), so closures
 * declared here never cross a Livewire serialization boundary.
 *
 * Everything that also makes sense at the model level (labels, titles,
 * attribute presentation, sentences) is delegated to a local ModelPresentation
 * which is merged on top of the global one at render time. That is what lets a
 * timeline locally override the global registry.
 */
final class Timeline implements Htmlable
{
    protected Model|Closure|null $record = null;

    protected ?string $source = null;

    protected string|Closure|null $heading = null;

    protected string|Closure|null $description = null;

    protected ?int $perPage = null;

    protected bool $withLoadMore = false;

    protected bool $withFilters = false;

    /**
     * @var list<string>|null
     */
    protected ?array $filterEvents = null;

    protected ModelPresentation $presentation;

    /**
     * @var array<string, string>
     */
    protected array $eventLabels = [];

    /**
     * @var array<string, string>
     */
    protected array $eventIcons = [];

    /**
     * @var array<string, string>
     */
    protected array $eventColors = [];

    protected ?Closure $titleFormatter = null;

    protected ?Closure $causerNameResolver = null;

    protected ?Closure $causerAvatarResolver = null;

    protected bool $debug = false;

    public function __construct(
        protected ?string $name = null,
    ) {
        $this->presentation = ModelPresentation::make();
    }

    public static function make(?string $name = null): static
    {
        return new self($name);
    }

    // -----------------------------------------------------------------
    // Record and source
    // -----------------------------------------------------------------

    public function record(Model|Closure $record): static
    {
        $this->record = $record;

        return $this;
    }

    public function for(Model|Closure $record): static
    {
        return $this->record($record);
    }

    public function resolveRecord(): ?Model
    {
        $record = $this->record instanceof Closure ? ($this->record)() : $this->record;

        return $record instanceof Model ? $record : null;
    }

    public function source(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    // -----------------------------------------------------------------
    // Header
    // -----------------------------------------------------------------

    public function heading(string|Closure $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(string|Closure $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->evaluateString($this->heading);
    }

    public function getDescription(): ?string
    {
        return $this->evaluateString($this->description);
    }

    // -----------------------------------------------------------------
    // Pagination and filters
    // -----------------------------------------------------------------

    public function limit(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function getPerPage(): ?int
    {
        return $this->perPage;
    }

    public function loadMore(bool $condition = true): static
    {
        $this->withLoadMore = $condition;

        return $this;
    }

    public function hasLoadMore(): bool
    {
        return $this->withLoadMore;
    }

    /**
     * @param  bool|array<array-key, string>  $events
     */
    public function filters(bool|array $events = true): static
    {
        if (is_array($events)) {
            $this->withFilters = true;
            $this->filterEvents = array_values($events);
        } else {
            $this->withFilters = $events;
        }

        return $this;
    }

    public function hasFilters(): bool
    {
        return $this->withFilters;
    }

    /**
     * @return list<string>|null
     */
    public function getFilterEvents(): ?array
    {
        return $this->filterEvents;
    }

    // -----------------------------------------------------------------
    // Event presentation overrides
    // -----------------------------------------------------------------

    /**
     * @param  array<string, string>  $labels
     */
    public function eventLabels(array $labels): static
    {
        $this->eventLabels = [...$this->eventLabels, ...$labels];

        return $this;
    }

    /**
     * @param  array<string, string>  $icons
     */
    public function eventIcons(array $icons): static
    {
        $this->eventIcons = [...$this->eventIcons, ...$icons];

        return $this;
    }

    /**
     * @param  array<string, string>  $colors
     */
    public function eventColors(array $colors): static
    {
        $this->eventColors = [...$this->eventColors, ...$colors];

        return $this;
    }

    public function getEventLabel(string $event): ?string
    {
        return $this->eventLabels[$event] ?? null;
    }

    public function getEventIcon(string $event): ?string
    {
        return $this->eventIcons[$event] ?? null;
    }

    public function getEventColor(string $event): ?string
    {
        return $this->eventColors[$event] ?? null;
    }

    // -----------------------------------------------------------------
    // Title, causer and sentence callbacks
    // -----------------------------------------------------------------

    public function formatTitleUsing(Closure $callback): static
    {
        $this->titleFormatter = $callback;

        return $this;
    }

    public function getTitleFormatter(): ?Closure
    {
        return $this->titleFormatter;
    }

    public function causerNameUsing(Closure $callback): static
    {
        $this->causerNameResolver = $callback;

        return $this;
    }

    public function getCauserNameResolver(): ?Closure
    {
        return $this->causerNameResolver;
    }

    public function causerAvatarUsing(Closure $callback): static
    {
        $this->causerAvatarResolver = $callback;

        return $this;
    }

    public function getCauserAvatarResolver(): ?Closure
    {
        return $this->causerAvatarResolver;
    }

    // -----------------------------------------------------------------
    // Local model presentation overrides (delegated)
    // -----------------------------------------------------------------

    public function presentation(): ModelPresentation
    {
        return $this->presentation;
    }

    public function modelLabel(string $label): static
    {
        $this->presentation->label($label);

        return $this;
    }

    public function pluralModelLabel(string $label): static
    {
        $this->presentation->pluralLabel($label);

        return $this;
    }

    public function recordTitleUsing(Closure $callback): static
    {
        $this->presentation->recordTitleUsing($callback);

        return $this;
    }

    /**
     * @param  list<string>  $attributes
     */
    public function recordTitleAttributes(array $attributes): static
    {
        $this->presentation->recordTitleAttributes($attributes);

        return $this;
    }

    /**
     * @param  array<string, AttributePresentation>  $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->presentation->attributes($attributes);

        return $this;
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function attributeLabels(array $labels): static
    {
        $this->presentation->attributeLabels($labels);

        return $this;
    }

    public function attributeLabel(string $name, string $label): static
    {
        $this->presentation->attributeLabel($name, $label);

        return $this;
    }

    public function formatAttributeUsing(string $name, Closure $callback): static
    {
        $this->presentation->formatAttributeUsing($name, $callback);

        return $this;
    }

    /**
     * @param  list<string>  $names
     */
    public function hiddenAttributes(array $names): static
    {
        $this->presentation->hiddenAttributes($names);

        return $this;
    }

    public function eventSentence(string $event, string $template): static
    {
        $this->presentation->eventSentence($event, $template);

        return $this;
    }

    public function eventSentenceUsing(string $event, Closure $callback): static
    {
        $this->presentation->eventSentenceUsing($event, $callback);

        return $this;
    }

    public function resource(string $resource): static
    {
        $this->presentation->resource($resource);

        return $this;
    }

    // -----------------------------------------------------------------
    // Diagnostics
    // -----------------------------------------------------------------

    public function debugPresentation(bool $condition = true): static
    {
        $this->debug = $condition;

        return $this;
    }

    public function hasDebug(): bool
    {
        return $this->debug;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    // -----------------------------------------------------------------
    // Rendering as a Livewire component (page embedding)
    // -----------------------------------------------------------------

    /**
     * Render the timeline in a page. Only serializable configuration crosses the
     * Livewire boundary; closures declared locally must instead live on the
     * global registry or on an ActivityTimelineWidget subclass.
     */
    public function toHtml(): string
    {
        return Blade::render(
            '@livewire($component, $props)',
            ['component' => ActivityTimelineWidget::class, 'props' => $this->toLivewireProps()],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toLivewireProps(): array
    {
        return array_filter([
            'record' => $this->resolveRecord(),
            'source' => $this->source,
            'perPage' => $this->perPage,
            'withLoadMore' => $this->withLoadMore,
            'withFilters' => $this->withFilters,
            'debug' => $this->debug,
            'heading' => $this->getHeading(),
            'description' => $this->getDescription(),
            'eventLabels' => $this->eventLabels,
            'eventIcons' => $this->eventIcons,
            'eventColors' => $this->eventColors,
            'presentationOverrides' => $this->serializablePresentationOverrides(),
        ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializablePresentationOverrides(): array
    {
        $presentation = $this->presentation;
        $overrides = [];

        if ($presentation->getLabel() !== null) {
            $overrides['modelLabel'] = $presentation->getLabel();
        }

        if ($presentation->getPluralLabel() !== null) {
            $overrides['pluralModelLabel'] = $presentation->getPluralLabel();
        }

        $labels = [];
        $hidden = [];

        foreach ($presentation->getAttributes() as $name => $attribute) {
            if ($attribute->getLabel() !== null) {
                $labels[$name] = $attribute->getLabel();
            }

            if ($attribute->isHidden()) {
                $hidden[] = $name;
            }
        }

        if ($labels !== []) {
            $overrides['attributeLabels'] = $labels;
        }

        if ($hidden !== []) {
            $overrides['hiddenAttributes'] = $hidden;
        }

        if ($presentation->getEventSentences() !== []) {
            $overrides['eventSentences'] = $presentation->getEventSentences();
        }

        return $overrides;
    }

    protected function evaluateString(string|Closure|null $value): ?string
    {
        if ($value instanceof Closure) {
            $value = $value();
        }

        return $value === null ? null : (string) $value;
    }
}
