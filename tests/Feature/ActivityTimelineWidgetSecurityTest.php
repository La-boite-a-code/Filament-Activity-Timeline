<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use LaBoiteACode\FilamentActivityTimeline\Registries\SourceRegistry;
use LaBoiteACode\FilamentActivityTimeline\Sources\SpatieActivitySource;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\RestrictedFilterTimelineWidget;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

// -----------------------------------------------------------------
// Locked state: the widget is configured on the server, never by the browser.
// -----------------------------------------------------------------

it('refuses a client update of the record', function (): void {
    $mine = Order::create(['number' => 'MINE']);
    $theirs = Order::create(['number' => 'THEIRS']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $mine, 'source' => 'spatie'])
        ->set('record', $theirs);
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses a client update of the source', function (): void {
    app(SourceRegistry::class)->register('audit', fn () => SpatieActivitySource::make());

    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->set('source', 'audit');
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses a client update of the debug flag', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->set('debug', true);
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses a client update of the page size', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->set('perPage', 999999);
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses a client update of the page step', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->set('step', 999999);
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses a client update of the presentation overrides', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'presentationOverrides' => ['hiddenAttributes' => ['internal_note']],
    ])->set('presentationOverrides', []);
})->throws(CannotUpdateLockedPropertyException::class);

it('keeps a widget hidden attribute masked for the whole session', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-1']);

    makeActivity(
        event: 'updated',
        properties: ['old' => ['internal_note' => 'fraud suspect'], 'attributes' => ['internal_note' => 'blacklisted']],
        subject: $order,
        causer: $user,
    );

    $widget = Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'presentationOverrides' => ['hiddenAttributes' => ['internal_note']],
    ]);

    $widget->assertDontSee('blacklisted');

    $widget->call('loadMore')
        ->assertOk()
        ->assertDontSee('blacklisted')
        ->assertDontSee('fraud suspect');
});

// -----------------------------------------------------------------
// Bounded pagination
// -----------------------------------------------------------------

it('stops growing the page size at the configured ceiling', function (): void {
    config()->set('filament-activity-timeline.pagination.max_per_page', 50);

    $order = Order::create(['number' => 'CMD-1']);

    $widget = Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'perPage' => 20,
        'withLoadMore' => true,
    ]);

    expect($widget->get('perPage'))->toBe(20);

    $widget->call('loadMore');
    expect($widget->get('perPage'))->toBe(40);

    $widget->call('loadMore');
    expect($widget->get('perPage'))->toBe(50);

    $widget->call('loadMore');
    expect($widget->get('perPage'))->toBe(50);
});

it('never truncates a first page larger than the ceiling', function (): void {
    config()->set('filament-activity-timeline.pagination.max_per_page', 50);

    $order = Order::create(['number' => 'CMD-1']);

    $widget = Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'perPage' => 200,
        'withLoadMore' => true,
    ]);

    expect($widget->get('perPage'))->toBe(200);

    $widget->call('loadMore');

    expect($widget->get('perPage'))->toBe(200);
});

// -----------------------------------------------------------------
// Validated event filter
// -----------------------------------------------------------------

it('ignores an event that is not an offered filter option', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'withFilters' => true,
    ])
        ->call('filterByEvent', 'not-an-offered-event')
        ->assertOk()
        ->assertSet('activeEvent', null);
});

it('keeps accepting an event that is an offered filter option', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-1']);

    makeActivity(event: 'created', subject: $order, causer: $user, description: 'the-creation');
    makeActivity(event: 'deleted', subject: $order, causer: $user, description: 'the-deletion');

    Livewire::test(ActivityTimelineWidget::class, [
        'record' => $order,
        'source' => 'spatie',
        'withFilters' => true,
    ])
        ->call('filterByEvent', 'created')
        ->assertOk()
        ->assertSet('activeEvent', 'created')
        ->assertSee('the-creation')
        ->assertDontSee('the-deletion');
});

it('honours an explicitly restricted filter list', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(RestrictedFilterTimelineWidget::class, ['record' => $order])
        ->call('filterByEvent', 'deleted')
        ->assertOk()
        ->assertSet('activeEvent', null);
});

// -----------------------------------------------------------------
// Exception handling
// -----------------------------------------------------------------

it('lets an authorization failure bubble up to the handler', function (): void {
    $this->withoutExceptionHandling();

    app(SourceRegistry::class)->register('guarded', function (): never {
        throw new AuthorizationException('This action is unauthorized.');
    });

    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'guarded']);
})->throws(AuthorizationException::class);

it('does not disguise a denied timeline as a record with no history', function (): void {
    app(SourceRegistry::class)->register('guarded', function (): never {
        throw new AuthorizationException('This action is unauthorized.');
    });

    $order = Order::create(['number' => 'CMD-1']);

    $html = Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'guarded'])->html();

    expect($html)
        ->not->toContain(__('filament-activity-timeline::timeline.error.heading'))
        ->not->toContain(__('filament-activity-timeline::timeline.empty.heading'));
});

it('still degrades to the error state for an unexpected failure', function (): void {
    app(SourceRegistry::class)->register('broken', function (): never {
        throw new RuntimeException('the activity table is gone');
    });

    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'broken'])
        ->assertOk()
        ->assertSee(__('filament-activity-timeline::timeline.error.heading'))
        ->assertDontSee('the activity table is gone');
});

it('still degrades to the error state for a misconfigured source', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'does-not-exist'])
        ->assertOk()
        ->assertSee(__('filament-activity-timeline::timeline.error.heading'));
});
