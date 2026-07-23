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

it('renders the section heading even when the public heading property is null', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    // Livewire shares public properties with the view after the explicit view
    // data, so view keys must not collide with null public properties.
    $html = Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->html();

    expect($html)->toMatch('/fi-section-header-heading[^>]*>\s*Activity\s*</');
});

it('renders a custom heading and description', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    $html = Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'heading' => 'Historique',
        'description' => 'Toutes les actions.',
    ])->html();

    expect($html)
        ->toMatch('/fi-section-header-heading[^>]*>\s*Historique\s*</')
        ->toMatch('/fi-section-header-description[^>]*>\s*Toutes les actions\.\s*</');
});

it('renders filter tabs whose wire:click handlers are compiled', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-1']);

    makeActivity(event: 'created', subject: $order, causer: $user);

    $html = Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'withFilters' => true,
    ])->html();

    // Blade never compiles directives inside component tag attributes, so a
    // literal "@js(" reaching the browser means the tabs are dead on click.
    expect($html)
        ->not->toContain('@js(')
        ->toContain("filterByEvent('created')")
        ->toContain("filterByEvent('')");
});

it('treats the all events tab as a null filter', function (): void {
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
        ->call('filterByEvent', '')
        ->assertSet('activeEvent', null);
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
