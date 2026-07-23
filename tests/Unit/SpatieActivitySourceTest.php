<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineEntry;
use LaBoiteACode\FilamentActivityTimeline\Exceptions\MissingDependency;
use LaBoiteACode\FilamentActivityTimeline\Sources\SpatieActivitySource;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;

function spatie(Order $order): SpatieActivitySource
{
    return SpatieActivitySource::make()->forRecord($order)->latestFirst();
}

it('normalizes a Spatie activity into a timeline entry', function (): void {
    $user = User::create(['name' => 'Alexandre']);
    $order = Order::create(['number' => 'CMD-1']);

    makeActivity(
        event: 'updated',
        properties: ['old' => ['status' => 'pending'], 'attributes' => ['status' => 'paid']],
        subject: $order,
        causer: $user,
    );

    $result = spatie($order)->paginate(10);

    expect($result->entries)->toHaveCount(1);

    $entry = $result->entries[0];

    expect($entry)->toBeInstanceOf(TimelineEntry::class)
        ->and($entry->event)->toBe('updated')
        ->and($entry->causer?->getKey())->toBe($user->getKey())
        ->and($entry->subject?->getKey())->toBe($order->getKey())
        ->and($entry->subjectType)->toBe(Order::class)
        ->and($entry->properties)->toBe(['old' => ['status' => 'pending'], 'attributes' => ['status' => 'paid']]);
});

it('orders activities newest first and can reverse', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    $first = makeActivity(event: 'created', subject: $order);
    $second = makeActivity(event: 'updated', subject: $order);

    $latestFirst = spatie($order)->paginate(10)->entries;
    expect($latestFirst[0]->id)->toBe($second->getKey());

    $oldestFirst = SpatieActivitySource::make()->forRecord($order)->latestFirst(false)->paginate(10)->entries;
    expect($oldestFirst[0]->id)->toBe($first->getKey());
});

it('filters activities by event on the server', function (): void {
    $order = Order::create(['number' => 'CMD-1']);
    makeActivity(event: 'created', subject: $order);
    makeActivity(event: 'deleted', subject: $order);

    $result = spatie($order)->events(['created'])->paginate(10);

    expect($result->entries)->toHaveCount(1)
        ->and($result->entries[0]->event)->toBe('created');
});

it('paginates with a cursor and reports whether more pages exist', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    foreach (range(1, 5) as $ignored) {
        makeActivity(event: 'updated', subject: $order);
    }

    $page = spatie($order)->paginate(2);

    expect($page->entries)->toHaveCount(2)
        ->and($page->hasMore)->toBeTrue()
        ->and($page->nextCursor)->not->toBeNull();

    $next = spatie($order)->paginate(2, $page->nextCursor);

    expect($next->entries)->toHaveCount(2)
        ->and($next->entries[0]->id)->not->toBe($page->entries[0]->id);
});

it('only returns activity that belongs to the given record', function (): void {
    $order = Order::create(['number' => 'CMD-1']);
    $other = Order::create(['number' => 'CMD-2']);

    makeActivity(event: 'created', subject: $order);
    makeActivity(event: 'created', subject: $other);

    expect(spatie($order)->paginate(10)->entries)->toHaveCount(1);
});

it('eager loads causer and subject without an N+1 query', function (): void {
    $order = Order::create(['number' => 'CMD-1']);

    foreach (range(1, 10) as $ignored) {
        $user = User::create(['name' => 'User']);
        makeActivity(event: 'updated', subject: $order, causer: $user);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $result = spatie($order)->paginate(10);
    $result->entries[0]->causer?->name;
    $result->entries[9]->causer?->name;

    // One query for the activities plus the eager loaded relations; nowhere
    // near one query per row.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(5)
        ->and($result->entries)->toHaveCount(10);
});

it('returns an empty result when no record is scoped', function (): void {
    expect(SpatieActivitySource::make()->paginate(10)->isEmpty())->toBeTrue();
});

it('fails with a clear message when the activity model is missing', function (): void {
    SpatieActivitySource::make('\\Does\\Not\\Exist');
})->throws(MissingDependency::class);
