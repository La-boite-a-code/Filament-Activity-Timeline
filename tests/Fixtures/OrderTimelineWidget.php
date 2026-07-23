<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures;

use LaBoiteACode\FilamentActivityTimeline\Support\PresentationContext;
use LaBoiteACode\FilamentActivityTimeline\Timeline;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;

class OrderTimelineWidget extends ActivityTimelineWidget
{
    protected function timeline(): Timeline
    {
        return Timeline::make()
            ->source('spatie')
            ->loadMore()
            ->filters()
            ->recordTitleUsing(fn (Order $order): string => $order->number)
            ->causerNameUsing(fn (?object $causer): string => $causer?->name ?? 'Le systeme')
            ->formatTitleUsing(
                fn (object $entry, PresentationContext $context): string => $context->causerName().' -> '.$context->subject(),
            );
    }
}
