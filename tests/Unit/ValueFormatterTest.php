<?php

declare(strict_types=1);

use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Support\ValueFormatter;
use LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures\OrderStatus;

function formatter(): ValueFormatter
{
    return new ValueFormatter(app('translator'), 'd/m/Y H:i', null, 40);
}

it('renders null, empty and boolean values as readable text', function (): void {
    expect(formatter()->format(null))->toBe('Not set')
        ->and(formatter()->format(''))->toBe('Empty')
        ->and(formatter()->format(true))->toBe('Yes')
        ->and(formatter()->format(false))->toBe('No');
});

it('renders a boolean format even when the value is false', function (): void {
    $attribute = AttributePresentation::make()->boolean();

    expect(formatter()->format(false, $attribute))->toBe('No')
        ->and(formatter()->format(1, $attribute))->toBe('Yes');
});

it('renders a Filament enum using its label', function (): void {
    $attribute = AttributePresentation::make()->enum(OrderStatus::class);

    expect(formatter()->format('paid', $attribute))->toBe('Payée')
        ->and(formatter()->format('pending', $attribute))->toBe('En attente');
});

it('renders money using the locale aware formatter', function (): void {
    $attribute = AttributePresentation::make()->money('EUR');

    expect(formatter()->format(1234.5, $attribute))->toContain('234');
});

it('renders dates using the configured format', function (): void {
    $attribute = AttributePresentation::make()->dateTime();

    expect(formatter()->format('2026-07-23 10:42:00', $attribute))->toBe('23/07/2026 10:42');
});

it('maps values through a lookup table', function (): void {
    $attribute = AttributePresentation::make()->map([
        'card' => 'Carte bancaire',
        'bank_transfer' => 'Virement bancaire',
    ]);

    expect(formatter()->format('card', $attribute))->toBe('Carte bancaire')
        ->and(formatter()->format('unknown', $attribute))->toBe('unknown');
});

it('renders a list of values', function (): void {
    $attribute = AttributePresentation::make()->list();

    expect(formatter()->format(['a', 'b', 'c'], $attribute))->toBe('a, b, c')
        ->and(formatter()->format([], $attribute))->toBe('None');
});

it('redacts and masks sensitive values', function (): void {
    expect(formatter()->format('secret', AttributePresentation::make()->redacted()))->toBe('Hidden');

    $masked = AttributePresentation::make()->maskUsing(fn (string $value): string => str_repeat('*', strlen($value)));

    expect(formatter()->format('abcd', $masked))->toBe('****');
});

it('truncates very long strings', function (): void {
    $long = str_repeat('a', 100);
    $result = formatter()->format($long);

    expect(mb_strlen($result))->toBeLessThanOrEqual(43)
        ->and($result)->toEndWith('...');
});
