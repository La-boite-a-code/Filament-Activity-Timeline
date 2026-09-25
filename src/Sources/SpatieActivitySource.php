<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Sources;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Cursor;
use LaBoiteACode\FilamentActivityTimeline\Contracts\ActivitySource;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineEntry;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineResult;
use LaBoiteACode\FilamentActivityTimeline\Exceptions\MissingDependency;

/**
 * Reads activity recorded by spatie/laravel-activitylog and normalizes it into
 * TimelineEntry objects. It never leaks a Spatie model to the presentation
 * layer and always scopes the query to a single subject.
 */
final class SpatieActivitySource implements ActivitySource
{
    protected ?Model $record = null;

    /**
     * @var list<string>
     */
    protected array $events = [];

    protected bool $latestFirst = true;

    /**
     * @param  class-string<Model>  $activityModel
     */
    public function __construct(
        protected string $activityModel,
    ) {}

    public static function make(?string $activityModel = null): self
    {
        $model = $activityModel
            ?? config('activitylog.activity_model')
            ?? '\Spatie\Activitylog\Models\Activity';

        if (! is_string($model) || ! class_exists(ltrim($model, '\\'))) {
            throw MissingDependency::forSpatie();
        }

        return new self(ltrim($model, '\\'));
    }

    public function forRecord(Model $record): static
    {
        $clone = clone $this;
        $clone->record = $record;

        return $clone;
    }

    public function events(array $events): static
    {
        $clone = clone $this;
        $clone->events = array_values(array_filter($events, 'is_string'));

        return $clone;
    }

    public function latestFirst(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->latestFirst = $condition;

        return $clone;
    }

    public function paginate(int $perPage, ?string $cursor = null): TimelineResult
    {
        if ($this->record === null) {
            return TimelineResult::empty();
        }

        $paginator = $this->query()->cursorPaginate(
            perPage: max(1, $perPage),
            cursorName: 'cursor',
            cursor: is_string($cursor) && $cursor !== '' ? Cursor::fromEncoded($cursor) : null,
        );

        $entries = array_map(
            fn (Model $activity): TimelineEntry => $this->normalize($activity),
            array_values($paginator->items()),
        );

        return new TimelineResult(
            entries: $entries,
            hasMore: $paginator->hasMorePages(),
            nextCursor: $paginator->nextCursor()?->encode(),
        );
    }

    protected function query(): Builder
    {
        $direction = $this->latestFirst ? 'desc' : 'asc';

        /** @var Builder $query */
        $query = $this->activityModel::query()
            ->with(['causer', 'subject'])
            ->forSubject($this->record)
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction);

        if ($this->events !== []) {
            $query->whereIn('event', $this->events);
        }

        return $query;
    }

    protected function normalize(Model $activity): TimelineEntry
    {
        // activitylog v5 records a model's changes in their own column, where
        // v4 kept them inside the properties. Merging them over the properties
        // gives every version the same shape, and a v5 activity logged with
        // withProperties() alone keeps the changes it carries there.
        $properties = array_merge(
            $this->arrayAttribute($activity, 'properties'),
            $this->arrayAttribute($activity, 'attribute_changes'),
        );

        $subjectType = $activity->getAttribute('subject_type');
        $createdAt = $activity->getAttribute('created_at');
        $description = $activity->getAttribute('description');

        return new TimelineEntry(
            id: $activity->getKey(),
            event: (string) ($activity->getAttribute('event') ?? ''),
            title: (string) ($description ?? ''),
            description: filled($description) ? (string) $description : null,
            causer: $activity->getAttribute('causer'),
            subject: $activity->getAttribute('subject'),
            subjectType: is_string($subjectType) ? $this->resolveMorphClass($subjectType) : null,
            subjectId: $activity->getAttribute('subject_id'),
            properties: $properties,
            occurredAt: $createdAt !== null
                ? CarbonImmutable::instance($createdAt)
                : CarbonImmutable::now(),
            batchUuid: $this->stringOrNull($this->attribute($activity, 'batch_uuid')),
        );
    }

    /**
     * Reads a column only one activitylog version has: v4 has no
     * attribute_changes and v5 no batch_uuid. Reading a column the row does
     * not carry throws in an application that prevents accessing missing
     * attributes, so it is read as null instead.
     */
    protected function attribute(Model $activity, string $key): mixed
    {
        return $activity->hasAttribute($key) ? $activity->getAttribute($key) : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function arrayAttribute(Model $activity, string $key): array
    {
        $value = $this->attribute($activity, $key);

        if (is_object($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        }

        return is_array($value) ? $value : [];
    }

    protected function resolveMorphClass(string $type): string
    {
        $mapped = Relation::getMorphedModel($type);

        return is_string($mapped) ? $mapped : $type;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
