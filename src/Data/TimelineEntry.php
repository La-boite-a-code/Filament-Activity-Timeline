<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Data;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * The single, immutable shape every activity source normalizes its data to.
 *
 * A source must never hand a framework specific model (such as a Spatie
 * Activity) to the presentation layer. It hands a TimelineEntry instead.
 */
final readonly class TimelineEntry
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(
        public string|int $id,
        public string $event,
        public string $title,
        public ?string $description,
        public ?Model $causer,
        public ?Model $subject,
        public ?string $subjectType,
        public string|int|null $subjectId,
        public array $properties,
        public CarbonImmutable $occurredAt,
        public ?string $batchUuid = null,
        public ?string $icon = null,
        public ?string $color = null,
    ) {}

    public function is(string $event): bool
    {
        return $this->event === $event;
    }

    public function changes(): ChangeSet
    {
        return ChangeSet::fromProperties($this->properties);
    }

    public function hasCauser(): bool
    {
        return $this->causer !== null;
    }

    /**
     * A stable identifier used both as a Blade loop key and to prevent
     * duplicates when new pages are appended.
     */
    public function key(): string
    {
        return $this->subjectType !== null
            ? $this->subjectType.':'.$this->id
            : (string) $this->id;
    }
}
