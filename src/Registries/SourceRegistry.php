<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Registries;

use Closure;
use LaBoiteACode\FilamentActivityTimeline\Contracts\ActivitySource;
use LaBoiteACode\FilamentActivityTimeline\Exceptions\SourceNotRegistered;

/**
 * Maps a source name to a factory that produces a fresh ActivitySource. A
 * factory is used, rather than a shared instance, because sources are stateful
 * (they are scoped to a record and to a set of events per timeline).
 */
final class SourceRegistry
{
    /**
     * @var array<string, Closure(): ActivitySource>
     */
    protected array $factories = [];

    /**
     * @param  Closure(): ActivitySource  $factory
     */
    public function register(string $name, Closure $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }

    public function make(string $name): ActivitySource
    {
        if (! $this->has($name)) {
            throw SourceNotRegistered::make($name, $this->names());
        }

        return ($this->factories[$name])();
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->factories);
    }
}
