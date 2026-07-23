<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Infolists;

use Closure;
use Filament\Schemas\Components\Component;
use LaBoiteACode\FilamentActivityTimeline\Data\RenderedEntry;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Support\TimelineRenderer;
use LaBoiteACode\FilamentActivityTimeline\Timeline;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;

/**
 * A schema component that places the timeline inside a Filament infolist or
 * schema. It reuses the interactive widget by default (load more and filters
 * work), and can also render a read only, non interactive list with static().
 *
 * The presentation (labels, formats, relations, business sentences) comes from
 * the global registry, exactly like the widget's drop in mode.
 */
class ActivityTimelineEntry extends Component
{
    protected string $view = 'filament-activity-timeline::infolists.activity-timeline-entry';

    protected string $viewIdentifier = 'activityTimeline';

    protected Timeline $timeline;

    protected bool $interactive = true;

    final public function __construct(?string $name = null)
    {
        $this->timeline = Timeline::make($name);
        $this->columnSpanFull();
    }

    public static function make(?string $name = 'activity'): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }

    // -----------------------------------------------------------------
    // Configuration (delegated to the wrapped Timeline)
    // -----------------------------------------------------------------

    public function source(string $source): static
    {
        $this->timeline->source($source);

        return $this;
    }

    public function heading(string|Closure $heading): static
    {
        $this->timeline->heading($heading);

        return $this;
    }

    public function description(string|Closure $description): static
    {
        $this->timeline->description($description);

        return $this;
    }

    public function perPage(int $perPage): static
    {
        $this->timeline->limit($perPage);

        return $this;
    }

    public function loadMore(bool $condition = true): static
    {
        $this->timeline->loadMore($condition);

        return $this;
    }

    /**
     * @param  bool|array<array-key, string>  $events
     */
    public function filters(bool|array $events = true): static
    {
        $this->timeline->filters($events);

        return $this;
    }

    public function debugPresentation(bool $condition = true): static
    {
        $this->timeline->debugPresentation($condition);

        return $this;
    }

    public function modelLabel(string $label): static
    {
        $this->timeline->modelLabel($label);

        return $this;
    }

    /**
     * @param  array<string, AttributePresentation>  $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->timeline->attributes($attributes);

        return $this;
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function attributeLabels(array $labels): static
    {
        $this->timeline->attributeLabels($labels);

        return $this;
    }

    /**
     * @param  list<string>  $names
     */
    public function hiddenAttributes(array $names): static
    {
        $this->timeline->hiddenAttributes($names);

        return $this;
    }

    public function eventSentence(string $event, string $template): static
    {
        $this->timeline->eventSentence($event, $template);

        return $this;
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function eventLabels(array $labels): static
    {
        $this->timeline->eventLabels($labels);

        return $this;
    }

    /**
     * @param  array<string, string>  $icons
     */
    public function eventIcons(array $icons): static
    {
        $this->timeline->eventIcons($icons);

        return $this;
    }

    /**
     * @param  array<string, string>  $colors
     */
    public function eventColors(array $colors): static
    {
        $this->timeline->eventColors($colors);

        return $this;
    }

    public function interactive(bool $condition = true): static
    {
        $this->interactive = $condition;

        return $this;
    }

    public function static(bool $condition = true): static
    {
        $this->interactive = ! $condition;

        return $this;
    }

    // -----------------------------------------------------------------
    // Rendering helpers consumed by the view
    // -----------------------------------------------------------------

    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    public function getWidgetClass(): string
    {
        return ActivityTimelineWidget::class;
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetProps(): array
    {
        return $this->resolvedTimeline()->toLivewireProps();
    }

    public function getComponentKey(): string
    {
        $record = $this->getRecord();
        $recordKey = $record !== null ? (string) $record->getKey() : 'none';

        return 'fat-'.($this->timeline->getName() ?? 'activity').'-'.$recordKey;
    }

    public function getStaticHeading(): string
    {
        return $this->timeline->getHeading()
            ?? (string) trans('filament-activity-timeline::timeline.heading');
    }

    public function getStaticDescription(): ?string
    {
        return $this->timeline->getDescription();
    }

    public function isDebug(): bool
    {
        return $this->timeline->hasDebug();
    }

    /**
     * @return list<RenderedEntry>
     */
    public function getStaticEntries(): array
    {
        $timeline = $this->resolvedTimeline();
        $renderer = app(TimelineRenderer::class);

        $perPage = $timeline->getPerPage()
            ?? (int) (config('filament-activity-timeline.pagination.per_page') ?? 20);

        return $renderer->render($timeline, $renderer->fetch($timeline, null, $perPage));
    }

    protected function resolvedTimeline(): Timeline
    {
        $record = $this->getRecord();

        if ($record !== null) {
            $this->timeline->record($record);
        }

        return $this->timeline;
    }
}
