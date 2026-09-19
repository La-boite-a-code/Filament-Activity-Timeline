<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures;

use LaBoiteACode\FilamentActivityTimeline\Timeline;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;

/**
 * Offers a deliberately narrow filter list so the tests can prove that the
 * widget only accepts an event it actually offers.
 */
class RestrictedFilterTimelineWidget extends ActivityTimelineWidget
{
    protected function timeline(): Timeline
    {
        return Timeline::make()
            ->source('spatie')
            ->filters(['created', 'updated']);
    }
}
