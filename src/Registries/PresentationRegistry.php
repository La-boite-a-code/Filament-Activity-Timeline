<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Registries;

use Closure;
use LaBoiteACode\FilamentActivityTimeline\Presentation\EventPresentation;
use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;

/**
 * Holds every globally declared presentation: one per model, one per named
 * event, plus optional presets. It is bound as a singleton so declarations made
 * in a service provider survive for the whole request.
 */
final class PresentationRegistry
{
    /**
     * @var array<class-string, ModelPresentation>
     */
    protected array $models = [];

    /**
     * @var array<string, EventPresentation>
     */
    protected array $events = [];

    /**
     * @var array<string, Closure(self): void>
     */
    protected array $presets = [];

    /**
     * Return the presentation for a model, creating an empty one on first use so
     * it can be configured fluently.
     *
     * @param  class-string  $modelClass
     */
    public function forModel(string $modelClass): ModelPresentation
    {
        return $this->models[$modelClass] ??= ModelPresentation::make();
    }

    /**
     * @param  class-string  $modelClass
     */
    public function hasModel(string $modelClass): bool
    {
        return isset($this->models[$modelClass]);
    }

    /**
     * @param  class-string  $modelClass
     */
    public function getModel(string $modelClass): ?ModelPresentation
    {
        return $this->models[$modelClass] ?? null;
    }

    public function event(string $key): EventPresentation
    {
        return $this->events[$key] ??= EventPresentation::make($key);
    }

    public function getEvent(string $key): ?EventPresentation
    {
        return $this->events[$key] ?? null;
    }

    /**
     * @return array<string, EventPresentation>
     */
    public function allEvents(): array
    {
        return $this->events;
    }

    /**
     * @param  Closure(self): void  $callback
     */
    public function registerPreset(string $name, Closure $callback): void
    {
        $this->presets[$name] = $callback;
    }

    public function applyPreset(string $name): void
    {
        if (isset($this->presets[$name])) {
            ($this->presets[$name])($this);
        }
    }
}
