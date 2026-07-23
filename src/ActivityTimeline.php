<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline;

use Closure;
use LaBoiteACode\FilamentActivityTimeline\Presentation\EventPresentation;
use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;
use LaBoiteACode\FilamentActivityTimeline\Registries\PresentationRegistry;
use LaBoiteACode\FilamentActivityTimeline\Registries\SourceRegistry;

/**
 * The ergonomic entry point to the global registries. It is not a Laravel
 * facade: every call resolves the real, container bound registry, which keeps a
 * single source of truth while offering a discoverable static API.
 */
final class ActivityTimeline
{
    /**
     * Declare or extend the presentation of a model.
     *
     * @param  class-string  $modelClass
     */
    public static function forModel(string $modelClass): ModelPresentation
    {
        return self::presentation()->forModel($modelClass);
    }

    /**
     * Declare the presentation of a model from its Filament resource, letting
     * the resource provide labels and the record title attribute.
     *
     * @param  class-string  $resourceClass
     */
    public static function forResource(string $resourceClass): ModelPresentation
    {
        $model = method_exists($resourceClass, 'getModel') ? $resourceClass::getModel() : null;

        $presentation = is_string($model)
            ? self::presentation()->forModel($model)
            : ModelPresentation::make();

        return $presentation->resource($resourceClass);
    }

    /**
     * Declare or extend the presentation of a named event.
     */
    public static function event(string $key): EventPresentation
    {
        return self::presentation()->event($key);
    }

    public static function preset(string $name): void
    {
        self::presentation()->applyPreset($name);
    }

    /**
     * @param  Closure(): Contracts\ActivitySource  $factory
     */
    public static function registerSource(string $name, Closure $factory): void
    {
        self::sources()->register($name, $factory);
    }

    public static function presentation(): PresentationRegistry
    {
        return app(PresentationRegistry::class);
    }

    public static function sources(): SourceRegistry
    {
        return app(SourceRegistry::class);
    }
}
