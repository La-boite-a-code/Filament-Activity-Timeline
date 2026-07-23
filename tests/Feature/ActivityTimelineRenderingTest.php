<?php

declare(strict_types=1);

use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\OrderTimelineWidget;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;
use Livewire\Livewire;

it('escapes HTML found in causer names and values', function (): void {
    $user = User::create(['name' => '<script>alert(1)</script>']);
    $order = Order::create(['number' => 'CMD-1']);

    makeActivity(event: 'updated', properties: [
        'old' => ['number' => 'A'],
        'attributes' => ['number' => '<b>hax</b>'],
    ], subject: $order, causer: $user);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertDontSee('<b>hax</b>', escape: false)
        ->assertSee('alert(1)');
});

it('falls back to the system causer when there is none', function (): void {
    $order = Order::create(['number' => 'CMD-1']);
    makeActivity(event: 'created', subject: $order, causer: null);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk()
        ->assertSee(__('filament-activity-timeline::timeline.causer.system'));
});

it('renders without crashing on malformed properties', function (): void {
    $order = Order::create(['number' => 'CMD-1']);
    makeActivity(event: 'updated', properties: ['old' => 'not-an-array', 'attributes' => 42], subject: $order);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk();
});

it('renders inside a Filament section so it follows the theme', function (): void {
    $order = Order::create(['number' => 'CMD-1']);
    makeActivity(event: 'created', subject: $order);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk()
        ->assertSeeHtml('fi-section')
        ->assertSeeHtml('fi-ta');
});

it('applies closures declared on a widget subclass', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-2026-0184']);

    ActivityTimeline::forModel(Order::class)->label('commande');

    makeActivity(event: 'updated', subject: $order, causer: $user);

    Livewire::test(OrderTimelineWidget::class, ['record' => $order])
        ->assertOk()
        ->assertSee('Alexandre -> CMD-2026-0184');
});
