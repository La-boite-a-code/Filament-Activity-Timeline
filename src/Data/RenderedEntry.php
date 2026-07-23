<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Data;

/**
 * A fully resolved timeline entry: the business sentence, its presentation and
 * the readable change list. This is the shape the Blade views consume, which
 * keeps the templates free of any resolution logic.
 */
final readonly class RenderedEntry
{
    /**
     * @param  list<RenderedChange>  $changes
     * @param  array<string, mixed>|null  $debug
     */
    public function __construct(
        public TimelineEntry $entry,
        public string $key,
        public string $event,
        public string $sentence,
        public string $eventLabel,
        public ?string $icon,
        public ?string $color,
        public string $causerName,
        public ?string $causerAvatar,
        public string $relativeDate,
        public string $absoluteDate,
        public ?string $description,
        public array $changes,
        public ?array $debug = null,
    ) {}

    public function hasCauserAvatar(): bool
    {
        return $this->causerAvatar !== null && $this->causerAvatar !== '';
    }

    public function hasChanges(): bool
    {
        return $this->changes !== [];
    }
}
