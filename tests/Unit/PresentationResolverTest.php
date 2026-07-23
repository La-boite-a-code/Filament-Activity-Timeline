<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineEntry;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Registries\PresentationRegistry;
use LaBoiteACode\FilamentActivityTimeline\Support\EventSentenceRenderer;
use LaBoiteACode\FilamentActivityTimeline\Support\PresentationResolver;
use LaBoiteACode\FilamentActivityTimeline\Support\RelationResolver;
use LaBoiteACode\FilamentActivityTimeline\Support\ValueFormatter;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Customer;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Document;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\OrderStatus;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;
use LaBoiteACode\FilamentActivityTimeline\Timeline;

function resolver(Timeline $timeline): PresentationResolver
{
    /** @var array<string, mixed> $config */
    $config = config('filament-activity-timeline');

    return new PresentationResolver(
        $timeline,
        app(PresentationRegistry::class),
        new ValueFormatter(app('translator'), $config['date_format'], null, 120),
        new RelationResolver(true),
        new EventSentenceRenderer,
        app('translator'),
        $config,
    );
}

/**
 * @param  array<string, mixed>  $properties
 */
function entry(
    string $event = 'updated',
    array $properties = [],
    mixed $subject = null,
    mixed $causer = null,
    ?string $subjectType = Order::class,
): TimelineEntry {
    return new TimelineEntry(
        id: 1,
        event: $event,
        title: $event,
        description: $event,
        causer: $causer,
        subject: $subject,
        subjectType: $subjectType,
        subjectId: is_object($subject) ? $subject->getKey() : 184,
        properties: $properties,
        occurredAt: CarbonImmutable::parse('2026-07-23 10:42:00'),
    );
}

it('resolves the model label from the registry, then falls back to a humanized name', function (): void {
    ActivityTimeline::forModel(Order::class)->label('commande')->pluralLabel('commandes');

    $resolver = resolver(Timeline::make());

    expect($resolver->modelLabel(Order::class))->toBe('commande')
        ->and($resolver->modelLabel(Order::class, plural: true))->toBe('commandes')
        ->and($resolver->modelLabel(Customer::class))->toBe('Customer')
        ->and($resolver->modelLabel(Customer::class, plural: true))->toBe('Customers');
});

it('resolves the record title using the configured callback', function (): void {
    ActivityTimeline::forModel(Order::class)->recordTitleUsing(fn (Order $order): string => $order->number);

    $order = Order::create(['number' => 'CMD-2026-0184']);

    expect(resolver(Timeline::make())->recordTitle(entry(subject: $order)))->toBe('CMD-2026-0184');
});

it('honors the model contract and its activity title', function (): void {
    $document = Document::create(['number' => 'DOC-9']);

    $resolver = resolver(Timeline::make());
    $entry = entry(subject: $document, subjectType: Document::class);

    expect($resolver->modelLabel(Document::class))->toBe('document')
        ->and($resolver->recordTitle($entry))->toBe('DOC-9');
});

it('gives the local timeline priority over the global registry', function (): void {
    ActivityTimeline::forModel(Order::class)->label('commande');

    $timeline = Timeline::make()->modelLabel('dossier');

    expect(resolver($timeline)->modelLabel(Order::class))->toBe('dossier');
});

it('names the causer, the system and the unknown causer', function (): void {
    $user = User::create(['name' => 'Alexandre']);

    expect(resolver(Timeline::make())->causerName($user))->toBe('Alexandre')
        ->and(resolver(Timeline::make())->causerName(null))->toBe('System');

    $custom = Timeline::make()->causerNameUsing(fn (?Model $causer): string => $causer?->name ?? 'Robot');

    expect(resolver($custom)->causerName(null))->toBe('Robot');
});

it('resolves event icon and color with the documented priority', function (): void {
    ActivityTimeline::event('order.paid')->label('Paiement')->icon('heroicon-o-banknotes')->color('success');

    $resolver = resolver(Timeline::make()->eventColors(['created' => 'primary']));

    expect($resolver->eventColor('created'))->toBe('primary')
        ->and($resolver->eventColor('updated'))->toBe('warning')
        ->and($resolver->eventLabel('order.paid'))->toBe('Paiement')
        ->and($resolver->eventIcon('order.paid'))->toBe('heroicon-o-banknotes')
        ->and($resolver->eventLabel('order.shipped'))->toBe('Order Shipped');
});

it('builds a business sentence from a template with variables', function (): void {
    ActivityTimeline::forModel(Order::class)
        ->recordTitleUsing(fn (Order $order): string => $order->number)
        ->eventSentence('updated', ':causer a modifie :subject.');

    $order = Order::create(['number' => 'CMD-2026-0184']);
    $user = User::create(['name' => 'Alexandre']);
    $entry = entry(event: 'updated', subject: $order, causer: $user);

    $resolver = resolver(Timeline::make());
    $sentence = $resolver->sentence($entry, $resolver->context($entry, $resolver->changes($entry)));

    expect($sentence)->toBe('Alexandre a modifie CMD-2026-0184.');
});

it('formats attribute changes, including enums, and skips hidden attributes', function (): void {
    ActivityTimeline::forModel(Order::class)->attributes([
        'status' => AttributePresentation::make('Statut')->enum(OrderStatus::class),
        'internal_token' => AttributePresentation::make()->hidden(),
    ]);

    $entry = entry(event: 'updated', properties: [
        'old' => ['status' => 'pending', 'internal_token' => 'abc'],
        'attributes' => ['status' => 'paid', 'internal_token' => 'xyz'],
    ]);

    $changes = resolver(Timeline::make())->changes($entry);

    expect($changes)->toHaveCount(1)
        ->and($changes[0]->label)->toBe('Statut')
        ->and($changes[0]->old)->toBe('En attente')
        ->and($changes[0]->new)->toBe('Payée');
});

it('hides globally configured sensitive attributes', function (): void {
    $entry = entry(event: 'updated', properties: [
        'old' => ['password' => 'a', 'name' => 'x'],
        'attributes' => ['password' => 'b', 'name' => 'y'],
    ]);

    $changes = resolver(Timeline::make())->changes($entry);

    expect(collect($changes)->pluck('attribute')->all())->toBe(['name']);
});

it('resolves a relation identifier to its title', function (): void {
    $old = Customer::create(['name' => 'ACME France']);
    $new = Customer::create(['name' => 'Dupont Conseil']);

    ActivityTimeline::forModel(Order::class)->attributes([
        'customer_id' => AttributePresentation::make('Client')->relationship('customer', titleAttribute: 'name'),
    ]);

    $entry = entry(event: 'updated', properties: [
        'old' => ['customer_id' => $old->getKey()],
        'attributes' => ['customer_id' => $new->getKey()],
    ]);

    $changes = resolver(Timeline::make())->changes($entry);

    expect($changes[0]->label)->toBe('Client')
        ->and($changes[0]->old)->toBe('ACME France')
        ->and($changes[0]->new)->toBe('Dupont Conseil');
});

it('prefers a relation snapshot over a live lookup', function (): void {
    ActivityTimeline::forModel(Order::class)->attributes([
        'customer_id' => AttributePresentation::make('Client')->relationship('customer', titleAttribute: 'name'),
    ]);

    $entry = entry(event: 'updated', properties: [
        'old' => ['customer_id' => 1],
        'attributes' => ['customer_id' => 2],
        'presentation' => [
            'attributes' => [
                'customer_id' => ['old_label' => 'ACME France', 'new_label' => 'Dupont Conseil'],
            ],
        ],
    ]);

    $changes = resolver(Timeline::make())->changes($entry);

    expect($changes[0]->old)->toBe('ACME France')
        ->and($changes[0]->new)->toBe('Dupont Conseil');
});

it('resolves relations across many entries without an N+1 query', function (): void {
    ActivityTimeline::forModel(Order::class)->attributes([
        'customer_id' => AttributePresentation::make('Client')->relationship('customer', titleAttribute: 'name'),
    ]);

    $customers = collect(range(1, 10))->map(fn (int $i): Customer => Customer::create(['name' => "Customer {$i}"]));

    $entries = $customers->map(fn (Customer $customer): TimelineEntry => entry(event: 'updated', properties: [
        'old' => ['customer_id' => 1],
        'attributes' => ['customer_id' => $customer->getKey()],
    ]))->all();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $rendered = resolver(Timeline::make())->render($entries);

    // A single grouped whereIn resolves every customer instead of one query
    // per row.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(2)
        ->and($rendered)->toHaveCount(10)
        ->and($rendered[9]->changes[0]->new)->toBe('Customer 10');
});

it('keeps the subject title from a snapshot when the subject is gone', function (): void {
    $entry = entry(event: 'deleted', subject: null, properties: [
        'presentation' => ['subject_label' => 'commande', 'subject_title' => 'CMD-2026-0184'],
    ]);

    $resolver = resolver(Timeline::make());

    expect($resolver->recordTitle($entry))->toBe('CMD-2026-0184')
        ->and($resolver->modelLabel($entry->subjectType, false, $entry))->toBe('commande');
});
