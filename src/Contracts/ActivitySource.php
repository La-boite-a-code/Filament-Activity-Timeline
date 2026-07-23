<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineResult;

/**
 * A source of activity for a given record. Implementations translate their own
 * storage (Spatie activity log, a custom table, an API) into normalized
 * TimelineEntry objects, keeping the presentation layer free of any coupling to
 * a specific logging library.
 *
 * Implementations must stay immutable friendly: the configuration methods
 * return a configured instance and the reading happens in paginate().
 */
interface ActivitySource
{
    /**
     * Scope the source to a single record.
     */
    public function forRecord(Model $record): static;

    /**
     * Restrict the results to the given event names. An empty array means "no
     * event filter".
     *
     * @param  list<string>  $events
     */
    public function events(array $events): static;

    /**
     * Order the results, newest first by default.
     */
    public function latestFirst(bool $condition = true): static;

    /**
     * Read a single page. Cursor pagination is preferred when the underlying
     * store supports it; the cursor is opaque to the caller.
     */
    public function paginate(int $perPage, ?string $cursor = null): TimelineResult;
}
