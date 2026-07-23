<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Support;

use Illuminate\Support\Arr;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineEntry;

/**
 * The read only view handed to sentence callbacks. It exposes already resolved,
 * already formatted values so a callback can build a business sentence without
 * touching the raw activity or the database.
 */
final readonly class PresentationContext
{
    /**
     * @param  array<string, string>  $newValues
     * @param  array<string, string>  $oldValues
     */
    public function __construct(
        public TimelineEntry $entry,
        protected string $causerName,
        protected string $subjectTitle,
        protected string $subjectLabel,
        protected string $date,
        protected array $newValues,
        protected array $oldValues,
    ) {}

    public function entry(): TimelineEntry
    {
        return $this->entry;
    }

    public function causerName(): string
    {
        return $this->causerName;
    }

    public function subject(): string
    {
        return $this->subjectTitle;
    }

    public function subjectTitle(): string
    {
        return $this->subjectTitle;
    }

    public function subjectLabel(): string
    {
        return $this->subjectLabel;
    }

    public function date(): string
    {
        return $this->date;
    }

    public function newValue(string $attribute): ?string
    {
        return $this->newValues[$attribute] ?? null;
    }

    public function oldValue(string $attribute): ?string
    {
        return $this->oldValues[$attribute] ?? null;
    }

    public function property(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->entry->properties, $path, $default);
    }

    public function changesCount(): int
    {
        return $this->entry->changes()->count();
    }
}
