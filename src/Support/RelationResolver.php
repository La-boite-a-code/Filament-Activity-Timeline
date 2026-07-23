<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Support;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Resolves relation identifiers (for example customer_id => "ACME France")
 * while guaranteeing that a timeline never issues one query per row.
 *
 * Identifiers are grouped by model and title attribute, looked up with a single
 * whereIn query per group, and cached for the lifetime of a render pass.
 */
final class RelationResolver
{
    /**
     * @var array<string, array<string, string|null>>
     */
    protected array $cache = [];

    public function __construct(
        protected bool $enabled = true,
    ) {}

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Load the titles for many identifiers at once.
     *
     * @param  class-string<Model>  $modelClass
     * @param  list<int|string>  $ids
     */
    public function preload(string $modelClass, string $titleAttribute, array $ids): void
    {
        if (! $this->enabled || $ids === [] || ! is_subclass_of($modelClass, Model::class)) {
            return;
        }

        $bucket = $this->bucket($modelClass, $titleAttribute);
        $known = $this->cache[$bucket] ?? [];

        $missing = [];
        foreach ($ids as $id) {
            if (! array_key_exists((string) $id, $known)) {
                $missing[(string) $id] = $id;
            }
        }

        if ($missing === []) {
            return;
        }

        try {
            $instance = new $modelClass;
            $keyName = $instance->getKeyName();

            $rows = $modelClass::query()
                ->whereIn($keyName, array_values($missing))
                ->pluck($titleAttribute, $keyName);

            foreach ($missing as $stringId => $id) {
                $resolved = $rows[$id] ?? null;
                $this->cache[$bucket][$stringId] = $resolved === null ? null : (string) $resolved;
            }
        } catch (Throwable) {
            // A missing table or column must never break the timeline render.
            foreach ($missing as $stringId => $id) {
                $this->cache[$bucket][$stringId] ??= null;
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function resolve(string $modelClass, string $titleAttribute, int|string|null $id): ?string
    {
        if ($id === null || ! $this->enabled) {
            return null;
        }

        $bucket = $this->bucket($modelClass, $titleAttribute);

        if (! array_key_exists((string) $id, $this->cache[$bucket] ?? [])) {
            $this->preload($modelClass, $titleAttribute, [$id]);
        }

        return $this->cache[$bucket][(string) $id] ?? null;
    }

    public function flush(): void
    {
        $this->cache = [];
    }

    protected function bucket(string $modelClass, string $titleAttribute): string
    {
        return $modelClass.'|'.$titleAttribute;
    }
}
