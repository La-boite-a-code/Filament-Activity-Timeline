<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use LaBoiteACode\FilamentActivityTimeline\Registries\PresentationRegistry;
use LaBoiteACode\FilamentActivityTimeline\Registries\SourceRegistry;
use LaBoiteACode\FilamentActivityTimeline\Sources\SpatieActivitySource;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class FilamentActivityTimelineServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-activity-timeline';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PresentationRegistry::class);
        $this->app->singleton(SourceRegistry::class);
    }

    public function packageBooted(): void
    {
        $this->registerDefaultSources();
        $this->registerLivewireComponents();
        $this->registerAssets();
    }

    protected function registerAssets(): void
    {
        FilamentAsset::register([
            Css::make('filament-activity-timeline', __DIR__.'/../resources/dist/filament-activity-timeline.css'),
        ], package: 'laboiteacode/filament-activity-timeline');
    }

    protected function registerDefaultSources(): void
    {
        $sources = $this->app->make(SourceRegistry::class);

        if (! $sources->has('spatie')) {
            // The factory only touches spatie/laravel-activitylog when the source
            // is actually used, so the package boots cleanly without it.
            $sources->register('spatie', fn (): SpatieActivitySource => SpatieActivitySource::make());
        }
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('filament-activity-timeline-widget', ActivityTimelineWidget::class);
    }
}
