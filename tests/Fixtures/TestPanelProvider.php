<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use LaBoiteACode\FilamentActivityTimeline\FilamentActivityTimelinePlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('test')
            ->path('test')
            ->plugins([
                FilamentActivityTimelinePlugin::make(),
            ]);
    }
}
