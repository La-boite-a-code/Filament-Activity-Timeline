<?php

declare(strict_types=1);

use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Customer;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\Order;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\OrderStatus;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\User;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;
use Livewire\Livewire;

it('renders a full localized scenario with every attribute format', function (): void {
    app()->setLocale('fr');

    $acme = Customer::create(['name' => 'ACME France']);
    $dupont = Customer::create(['name' => 'Dupont Conseil']);
    $user = User::create(['name' => 'Alexandre Ribes']);
    $order = Order::create(['number' => 'CMD-2026-0184']);

    ActivityTimeline::forModel(Order::class)
        ->recordTitleUsing(fn (Order $record): string => $record->number)
        ->eventSentence('updated', ':causer a modifié :subject.')
        ->attributes([
            'status' => AttributePresentation::make('Statut')->enum(OrderStatus::class),
            'total' => AttributePresentation::make('Montant total')->money('EUR'),
            'paid_at' => AttributePresentation::make('Date de règlement')->dateTime(),
            'customer_id' => AttributePresentation::make('Client')->relationship('customer', titleAttribute: 'name'),
            'internal_token' => AttributePresentation::make()->hidden(),
        ]);

    makeActivity(event: 'updated', properties: [
        'old' => ['status' => 'pending', 'total' => null, 'paid_at' => null, 'customer_id' => $acme->getKey(), 'internal_token' => 'a1b2'],
        'attributes' => ['status' => 'paid', 'total' => 1250, 'paid_at' => '2026-07-23 10:42:00', 'customer_id' => $dupont->getKey(), 'internal_token' => 'c3d4'],
    ], subject: $order, causer: $user);

    Livewire::test(ActivityTimelineWidget::class, ['record' => $order, 'source' => 'spatie'])
        ->assertOk()
        // business sentence
        ->assertSee('Alexandre Ribes a modifié CMD-2026-0184.')
        // enum
        ->assertSee('Statut')->assertSee('En attente')->assertSee('Payée')
        // money (label kept, value formatted)
        ->assertSee('Montant total')->assertSee('250')
        // date time
        ->assertSee('Date de règlement')->assertSee('23/07/2026 10:42')
        // relation resolved to titles
        ->assertSee('Client')->assertSee('ACME France')->assertSee('Dupont Conseil')
        // hidden attribute never leaks its values
        ->assertDontSee('a1b2')->assertDontSee('c3d4');
});
