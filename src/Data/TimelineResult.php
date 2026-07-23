<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Data;

/**
 * A single page of normalized timeline entries plus the information needed to
 * request the next page without re-reading the current one.
 */
final readonly class TimelineResult
{
    /**
     * @param  list<TimelineEntry>  $entries
     */
    public function __construct(
        public array $entries,
        public bool $hasMore = false,
        public ?string $nextCursor = null,
        public ?int $total = null,
    ) {}

    public static function empty(): self
    {
        return new self([], false, null, 0);
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
