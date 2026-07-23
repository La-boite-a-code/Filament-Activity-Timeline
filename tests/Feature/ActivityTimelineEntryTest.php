<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Infolists\ActivityTimelineEntry;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\InfolistHostComponent;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;
use Livewire\Livewire;

function bindEntry(ActivityTimelineEntry $entry, Model $record): ActivityTimelineEntry
{
    $schema = Schema::make(null)->record($record)->components([$entry]);

    // Container assignment happens lazily when the components are traversed.
    $schema->getComponents();

    return $entry;
}

it('reads the record from the schema and builds interactive widget props', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    $entry = ActivityTimelineEntry::make('activity')
        ->source('spatie')
        ->loadMore()
        ->filters();

    bindEntry($entry, $order);

    expect($entry->isInteractive())->toBeTrue()
        ->and($entry->getRecord()?->getKey())->toBe($order->getKey())
        ->and($entry->getWidgetClass())->toBe(ActivityTimelineWidget::class);

    $props = $entry->getWidgetProps();

    expect($props['source'])->toBe('spatie')
        ->and($props['withLoadMore'])->toBeTrue()
        ->and($props['withFilters'])->toBeTrue()
        ->and($props['record']?->getKey())->toBe($order->getKey());
});

it('renders the interactive entry as the nested timeline widget in a schema', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-2026-0184']);

    ActivityTimeline::forModel(Order::class)
        ->recordTitleUsing(fn (Order $record): string => $record->number)
        ->eventSentence('updated', ':causer a modifie :subject.');

    makeActivity(event: 'updated', subject: $order, causer: $user);

    Livewire::test(InfolistHostComponent::class, ['record' => $order])
        ->assertOk()
        ->assertSee('Alexandre a modifie CMD-2026-0184')
        ->assertSeeHtml('fi-at');
});

it('renders a read only static list without load more or filters', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-9']);

    ActivityTimeline::forModel(Order::class)
        ->recordTitleUsing(fn (Order $record): string => $record->number)
        ->eventSentence('created', ':causer a cree :subject.');

    makeActivity(event: 'created', subject: $order, causer: $user);

    Livewire::test(InfolistHostComponent::class, ['record' => $order, 'useStatic' => true])
        ->assertOk()
        ->assertSee('Alexandre a cree CMD-9')
        ->assertSeeHtml('fi-section')
        ->assertDontSeeHtml('wire:click="loadMore"')
        ->assertDontSeeHtml('wire:click="filterByEvent');
});

it('shows the static empty state for a record without activity', function (): void {
    $order = Order::create(['number' => 'CMD-EMPTY']);

    Livewire::test(InfolistHostComponent::class, ['record' => $order, 'useStatic' => true])
        ->assertOk()
        ->assertSee(__('filament-activity-timeline::timeline.empty.heading'));
});

it('defaults to a full column span', function (): void {
    $entry = ActivityTimelineEntry::make('activity');

    expect($entry->getColumnSpan())->toBe(['default' => 'full']);
});
