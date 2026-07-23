<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Data;

use Illuminate\Support\Arr;

/**
 * A normalized view over the "old" and "attributes" arrays recorded for an
 * activity. It is resilient to malformed properties and never assumes a given
 * shape without checking it first.
 */
final readonly class ChangeSet
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function __construct(
        public array $old,
        public array $new,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function fromProperties(array $properties): self
    {
        $old = Arr::get($properties, 'old', []);
        $new = Arr::get($properties, 'attributes', []);

        return new self(
            is_array($old) ? $old : [],
            is_array($new) ? $new : [],
        );
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * The union of every attribute name that appears on either side, keeping
     * the order in which they were first seen.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        $keys = [];

        foreach (array_keys($this->new) as $key) {
            $keys[(string) $key] = true;
        }

        foreach (array_keys($this->old) as $key) {
            $keys[(string) $key] = true;
        }

        return array_keys($keys);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->new) || array_key_exists($key, $this->old);
    }

    public function changed(string $key): bool
    {
        return $this->oldValue($key) !== $this->newValue($key);
    }

    public function oldValue(string $key): mixed
    {
        return $this->old[$key] ?? null;
    }

    public function newValue(string $key): mixed
    {
        return $this->new[$key] ?? null;
    }

    public function count(): int
    {
        return count($this->keys());
    }

    public function isEmpty(): bool
    {
        return $this->keys() === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }
}
