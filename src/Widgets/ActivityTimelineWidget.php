<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineResult;
use LaBoiteACode\FilamentActivityTimeline\Registries\PresentationRegistry;
use LaBoiteACode\FilamentActivityTimeline\Support\PresentationResolver;
use LaBoiteACode\FilamentActivityTimeline\Support\TimelineRenderer;
use LaBoiteACode\FilamentActivityTimeline\Timeline;
use Livewire\Attributes\Locked;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The interactive host of a timeline. It is a Filament widget, hence a Livewire
 * component, and only stores serializable state (the record, the source name,
 * the active filter and the size of the visible window). Everything that cannot
 * be serialized (closures) is rebuilt on every request by the timeline() method
 * or comes from the global registry.
 *
 * Every public property is locked: Livewire lets a browser write to any public
 * property that is not, and none of this state is a user input. The widget is
 * configured on the server, through mount() or by extending timeline(), and the
 * only state the browser may move is moved through loadMore() and
 * filterByEvent(), which validate what they are given. Locking blocks client
 * updates only, so both actions keep working.
 *
 * Extend this class to configure a timeline with full access to closures, or
 * drop it in a page with plain properties and rely on the global registry.
 */
class ActivityTimelineWidget extends Widget
{
    protected string $view = 'filament-activity-timeline::widgets.activity-timeline-widget';

    protected int|string|array $columnSpan = 'full';

    #[Locked]
    public ?Model $record = null;

    #[Locked]
    public ?string $source = null;

    #[Locked]
    public ?string $activeEvent = null;

    #[Locked]
    public int $perPage = 20;

    #[Locked]
    public int $step = 20;

    #[Locked]
    public bool $withFilters = false;

    #[Locked]
    public bool $withLoadMore = false;

    #[Locked]
    public bool $debug = false;

    #[Locked]
    public ?string $heading = null;

    #[Locked]
    public ?string $description = null;

    /**
     * @var array<string, string>
     */
    #[Locked]
    public array $eventLabels = [];

    /**
     * @var array<string, string>
     */
    #[Locked]
    public array $eventIcons = [];

    /**
     * @var array<string, string>
     */
    #[Locked]
    public array $eventColors = [];

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $presentationOverrides = [];

    protected bool $errored = false;

    /**
     * @param  array<string, string>  $eventLabels
     * @param  array<string, string>  $eventIcons
     * @param  array<string, string>  $eventColors
     * @param  array<string, mixed>  $presentationOverrides
     */
    public function mount(
        ?Model $record = null,
        ?string $source = null,
        ?int $perPage = null,
        bool $withLoadMore = false,
        bool $withFilters = false,
        bool $debug = false,
        ?string $heading = null,
        ?string $description = null,
        array $eventLabels = [],
        array $eventIcons = [],
        array $eventColors = [],
        array $presentationOverrides = [],
    ): void {
        $this->record ??= $record;
        $this->source ??= $source;
        $this->withLoadMore = $withLoadMore || $this->withLoadMore;
        $this->withFilters = $withFilters || $this->withFilters;
        $this->debug = $debug || $this->debug;
        $this->heading ??= $heading;
        $this->description ??= $description;
        $this->eventLabels = $eventLabels ?: $this->eventLabels;
        $this->eventIcons = $eventIcons ?: $this->eventIcons;
        $this->eventColors = $eventColors ?: $this->eventColors;
        $this->presentationOverrides = $presentationOverrides ?: $this->presentationOverrides;

        $configured = $this->resolveTimeline();

        $this->perPage = max(1, $perPage ?? $configured->getPerPage() ?? (int) (config('filament-activity-timeline.pagination.per_page') ?? 20));
        $this->step = $this->perPage;
    }

    /**
     * Override this method to configure the timeline with closures and complex
     * attribute presentations. The default implementation builds a timeline
     * from the widget's serializable properties.
     */
    protected function timeline(): Timeline
    {
        $timeline = Timeline::make();

        if ($this->source !== null) {
            $timeline->source($this->source);
        }

        if ($this->heading !== null) {
            $timeline->heading($this->heading);
        }

        if ($this->description !== null) {
            $timeline->description($this->description);
        }

        if ($this->eventLabels !== []) {
            $timeline->eventLabels($this->eventLabels);
        }

        if ($this->eventIcons !== []) {
            $timeline->eventIcons($this->eventIcons);
        }

        if ($this->eventColors !== []) {
            $timeline->eventColors($this->eventColors);
        }

        if ($this->withLoadMore) {
            $timeline->loadMore();
        }

        if ($this->withFilters) {
            $timeline->filters();
        }

        if ($this->debug) {
            $timeline->debugPresentation();
        }

        $this->applySerializableOverrides($timeline);

        return $timeline;
    }

    public function loadMore(): void
    {
        $this->perPage = min($this->perPage + $this->step, $this->maxPerPage());
    }

    /**
     * Only an event the widget actually offers as a filter option is accepted.
     * Anything else falls back to the unfiltered timeline rather than reaching
     * the source, so a crafted request cannot query an event the timeline was
     * never configured to expose.
     */
    public function filterByEvent(?string $event): void
    {
        $this->perPage = $this->step;

        if ($event === null || $event === '') {
            $this->activeEvent = null;

            return;
        }

        $this->activeEvent = in_array($event, $this->allowedEvents($this->resolveTimeline()), true)
            ? $event
            : null;
    }

    public function render(): View
    {
        return view($this->view, $this->getViewData());
    }

    /**
     * Livewire shares every public property with the view after the explicit
     * view data, so these keys must never collide with a public property name
     * (a null $heading would silently erase the resolved heading).
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $timeline = $this->resolveTimeline();
        $renderer = app(TimelineRenderer::class);
        $result = $this->readActivity($renderer, $timeline);
        $resolver = $renderer->resolver($timeline);

        return [
            'timeline' => $timeline,
            'sectionHeading' => $timeline->getHeading()
                ?? (string) trans('filament-activity-timeline::timeline.heading'),
            'sectionDescription' => $timeline->getDescription(),
            'renderedEntries' => $resolver->render($result->entries),
            'hasMore' => $result->hasMore && $timeline->hasLoadMore(),
            'showFilters' => $timeline->hasFilters(),
            'filterOptions' => $timeline->hasFilters() ? $this->filterOptions($timeline, $resolver) : [],
            'errored' => $this->errored,
            'showDebug' => $timeline->hasDebug(),
        ];
    }

    protected function resolveTimeline(): Timeline
    {
        $timeline = $this->timeline();

        $record = $this->getRecord();

        if ($record !== null) {
            $timeline->record($record);
        }

        return $timeline;
    }

    protected function getRecord(): ?Model
    {
        return $this->record;
    }

    /**
     * The ceiling the visible window may grow to. A first page the developer
     * configured larger than the ceiling is always honoured in full, so raising
     * per_page never silently truncates a timeline.
     */
    protected function maxPerPage(): int
    {
        $configured = config('filament-activity-timeline.pagination.max_per_page');

        return max(1, is_numeric($configured) ? (int) $configured : 500, $this->step);
    }

    protected function readActivity(TimelineRenderer $renderer, Timeline $timeline): TimelineResult
    {
        $this->errored = false;

        try {
            return $renderer->fetch(
                $timeline,
                $this->activeEvent,
                min($this->perPage, $this->maxPerPage()),
            );
        } catch (Throwable $exception) {
            if ($this->shouldNotSwallow($exception)) {
                throw $exception;
            }

            report($exception);
            $this->errored = true;

            return TimelineResult::empty();
        }
    }

    /**
     * Authorization, authentication, validation and the framework's own
     * response control flow must reach the handler untouched. Rendering a
     * denied timeline as a generic error hides a policy decision behind what
     * reads like a record with no history, and swallowing a redirect or a
     * response exception strands the request on the page.
     */
    protected function shouldNotSwallow(Throwable $exception): bool
    {
        return $exception instanceof AuthorizationException
            || $exception instanceof AuthenticationException
            || $exception instanceof ValidationException
            || $exception instanceof HttpResponseException
            || $exception instanceof HttpExceptionInterface;
    }

    /**
     * The events offered as filter options, which is also the allow list
     * filterByEvent() validates against.
     *
     * @return list<string>
     */
    protected function allowedEvents(Timeline $timeline): array
    {
        $events = $timeline->getFilterEvents();

        if ($events === null) {
            /** @var array<string, mixed> $configured */
            $configured = config('filament-activity-timeline.events', []);
            $events = array_keys(array_merge(
                $configured,
                app(PresentationRegistry::class)->allEvents(),
            ));
        }

        return array_values(array_filter($events, 'is_string'));
    }

    /**
     * @return list<array{value: string|null, label: string, active: bool}>
     */
    protected function filterOptions(Timeline $timeline, PresentationResolver $resolver): array
    {
        $options = [[
            'value' => null,
            'label' => (string) trans('filament-activity-timeline::timeline.filters.all'),
            'active' => $this->activeEvent === null,
        ]];

        foreach ($this->allowedEvents($timeline) as $event) {
            $options[] = [
                'value' => $event,
                'label' => $resolver->eventLabel($event),
                'active' => $this->activeEvent === $event,
            ];
        }

        return $options;
    }

    protected function applySerializableOverrides(Timeline $timeline): void
    {
        $overrides = $this->presentationOverrides;

        if (isset($overrides['modelLabel']) && is_string($overrides['modelLabel'])) {
            $timeline->modelLabel($overrides['modelLabel']);
        }

        if (isset($overrides['pluralModelLabel']) && is_string($overrides['pluralModelLabel'])) {
            $timeline->pluralModelLabel($overrides['pluralModelLabel']);
        }

        if (isset($overrides['attributeLabels']) && is_array($overrides['attributeLabels'])) {
            /** @var array<string, string> $labels */
            $labels = array_filter($overrides['attributeLabels'], 'is_string');
            $timeline->attributeLabels($labels);
        }

        if (isset($overrides['hiddenAttributes']) && is_array($overrides['hiddenAttributes'])) {
            $timeline->hiddenAttributes(array_values(array_filter($overrides['hiddenAttributes'], 'is_string')));
        }

        if (isset($overrides['eventSentences']) && is_array($overrides['eventSentences'])) {
            foreach ($overrides['eventSentences'] as $event => $template) {
                if (is_string($event) && is_string($template)) {
                    $timeline->eventSentence($event, $template);
                }
            }
        }
    }
}
