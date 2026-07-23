<?php

declare(strict_types=1);

use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\OrderStatus;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;
use Livewire\Livewire;

it('shows the empty state when a record has no activity', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk()
        ->assertSee(__('filament-activity-timeline::timeline.empty.heading'));
});

it('renders business sentences for several activities', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-2026-0184']);

    ActivityTimeline::forModel(Order::class)
        ->label('commande')
        ->recordTitleUsing(fn (Order $record): string => $record->number)
        ->eventSentence('updated', ':causer a marque :subject comme payee.')
        ->attributes([
            'status' => AttributePresentation::make('Statut')->enum(OrderStatus::class),
        ]);

    makeActivity(event: 'created', subject: $order, causer: $user);
    makeActivity(
        event: 'updated',
        properties: ['old' => ['status' => 'pending'], 'attributes' => ['status' => 'paid']],
        subject: $order,
        causer: $user,
    );

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk()
        ->assertSee('Alexandre')
        ->assertSee('CMD-2026-0184')
        ->assertSee('payee')
        ->assertSee('Payée')
        ->assertSee('En attente');
});

it('filters activity by event on the server', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-1']);

    makeActivity(event: 'created', subject: $order, causer: $user);
    makeActivity(event: 'deleted', subject: $order, causer: $user);

    Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'withFilters' => true,
    ])
        ->call('filterByEvent', 'created')
        ->assertSet('activeEvent', 'created')
        ->assertSee(__('filament-activity-timeline::timeline.events.created'));
});

it('loads more without duplicating entries', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-1']);

    foreach (range(1, 5) as $i) {
        makeActivity(event: 'updated', subject: $order, causer: $user);
    }

    $component = Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'perPage' => 2,
        'withLoadMore' => true,
    ]);

    $component->assertSet('perPage', 2);
    $component->call('loadMore')->assertSet('perPage', 4);
});
