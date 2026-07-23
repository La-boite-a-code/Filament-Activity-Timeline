<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * The Filament plugin. Registering it on a panel is enough to use the timeline;
 * the package works with sensible defaults and no further configuration.
 */
final class FilamentActivityTimelinePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-activity-timeline';
    }

    public function register(Panel $panel): void
    {
        // The widget is registered as a Livewire component by the service
        // provider and can be added to any page or resource; nothing panel
        // specific is required here for the default experience.
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new self;
    }
}
