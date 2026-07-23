<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Support;

use Illuminate\Contracts\Translation\Translator;
use LaBoiteACode\FilamentActivityTimeline\Data\RenderedEntry;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineResult;
use LaBoiteACode\FilamentActivityTimeline\Registries\PresentationRegistry;
use LaBoiteACode\FilamentActivityTimeline\Registries\SourceRegistry;
use LaBoiteACode\FilamentActivityTimeline\Timeline;

/**
 * Builds the resolver, reads a page from the configured source and renders it.
 * Shared by the widget and the infolist entry so the wiring lives in one place.
 */
final class TimelineRenderer
{
    public function __construct(
        protected SourceRegistry $sources,
        protected PresentationRegistry $registry,
        protected Translator $translator,
    ) {}

    public function resolver(Timeline $timeline): PresentationResolver
    {
        $config = $this->config();

        $formatter = new ValueFormatter(
            translator: $this->translator,
            dateFormat: $this->stringConfig($config, 'date_format'),
            timezone: $this->stringConfig($config, 'timezone'),
            truncate: is_numeric($config['attributes']['truncate'] ?? null) ? (int) $config['attributes']['truncate'] : 120,
        );

        $relations = new RelationResolver((bool) ($config['relations']['resolve'] ?? true));

        return new PresentationResolver(
            timeline: $timeline,
            registry: $this->registry,
            formatter: $formatter,
            relations: $relations,
            sentences: new EventSentenceRenderer,
            translator: $this->translator,
            config: $config,
        );
    }

    public function fetch(Timeline $timeline, ?string $activeEvent, int $perPage): TimelineResult
    {
        $record = $timeline->resolveRecord();

        if ($record === null) {
            return TimelineResult::empty();
        }

        $sourceName = $timeline->getSource()
            ?? (string) ($this->config()['default_source'] ?? 'spatie');

        $source = $this->sources->make($sourceName)->forRecord($record)->latestFirst();

        if ($activeEvent !== null) {
            $source = $source->events([$activeEvent]);
        }

        return $source->paginate(max(1, $perPage));
    }

    /**
     * @return list<RenderedEntry>
     */
    public function render(Timeline $timeline, TimelineResult $result): array
    {
        return $this->resolver($timeline)->render($result->entries);
    }

    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('filament-activity-timeline', []);

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function stringConfig(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
