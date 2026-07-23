# Filament Activity Timeline

**The semantic activity timeline for Filament.**

> Turn technical activity logs into clear, localized business events.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laboiteacode/filament-activity-timeline.svg?style=flat-square)](https://packagist.org/packages/laboiteacode/filament-activity-timeline)
[![Tests](https://img.shields.io/github/actions/workflow/status/la-boite-a-code/filament-activity-timeline/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/la-boite-a-code/filament-activity-timeline/actions/workflows/run-tests.yml)
[![Static Analysis](https://img.shields.io/github/actions/workflow/status/la-boite-a-code/filament-activity-timeline/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/la-boite-a-code/filament-activity-timeline/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laboiteacode/filament-activity-timeline.svg?style=flat-square)](https://packagist.org/packages/laboiteacode/filament-activity-timeline)
[![License](https://img.shields.io/packagist/l/laboiteacode/filament-activity-timeline.svg?style=flat-square)](LICENSE.md)

Stop showing `customer_id: 14 -> 27`. Show `Client: ACME France -> Dupont Conseil`.

![Filament Activity Timeline](art/banner.jpg)

## Table of contents

- [The idea](#the-idea)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Registering the plugin](#registering-the-plugin)
- [Quick start](#quick-start)
- [Basic usage](#basic-usage)
  - [As a widget](#as-a-widget)
  - [In a page](#in-a-page)
  - [In an infolist](#in-an-infolist)
  - [Drop-in widget properties](#drop-in-widget-properties)
- [Using the semantic registry](#using-the-semantic-registry)
- [Customizing events](#customizing-events)
- [Customizing the causer](#customizing-the-causer)
- [Displaying changes](#displaying-changes)
- [Filters](#filters)
- [Pagination](#pagination)
- [Theming](#theming)
- [Translations](#translations)
- [Configuration](#configuration)
- [Adding a source](#adding-a-source)
- [Testing](#testing)
- [Credits](#credits)

## The idea

Several packages already display a timeline of `spatie/laravel-activitylog`
events. This one is different: it is a reusable, localized **presentation layer**
that turns raw model changes into sentences your users actually understand,
without spreading callbacks across every Filament resource.

From a technical activity such as:

```text
App\Models\Order
event: updated
subject_id: 184
properties:
    old.status: pending
    attributes.status: paid
```

it produces, once the presentation is configured:

```text
Alexandre marked order CMD-2026-0184 as paid.
```

And for several changes:

```text
Alexandre updated order CMD-2026-0184
3 changes
- Status: Pending -> Paid
- Payment date: Not set -> 23 July 2026 10:42
- Payment method: Not set -> Credit card
```

## Features

- A ready to use `ActivityTimelineWidget` and a declarative `Timeline` component.
- An `ActivityTimelineEntry` schema component for infolists, interactive by default with a read only `static()` mode.
- A `spatie/laravel-activitylog` source, with cursor pagination and server side filters.
- A per model semantic registry: labels, business record titles, icons and colors.
- Human readable formats: text, boolean, date, date time, money, enum, list, json, map and relationships.
- Localized null, empty and boolean values.
- Sensitive attributes hidden, redacted or masked by default.
- Relation identifiers resolved to titles, with no N+1 queries.
- Optional subject and relation snapshots, so titles survive deletion.
- Translatable business sentences per event, and custom events with their own icon and color.
- English and French translations out of the box.
- Native Filament look: every color comes from the panel theme variables, so custom themes, custom registered colors, dark mode and responsive layouts work with zero configuration.

## Requirements

- PHP 8.3, 8.4 or 8.5
- Laravel 12 or 13
- Filament 4 or 5
- `spatie/laravel-activitylog` 4.12 or 5 (only when using the Spatie source)

## Installation

```bash
composer require laboiteacode/filament-activity-timeline
```

When you use the Spatie integration:

```bash
composer require spatie/laravel-activitylog

php artisan vendor:publish \
    --provider="Spatie\Activitylog\ActivitylogServiceProvider" \
    --tag="activitylog-migrations"

php artisan migrate
```

Optionally publish the configuration and the translations:

```bash
php artisan vendor:publish --tag="filament-activity-timeline-config"
php artisan vendor:publish --tag="filament-activity-timeline-translations"
```

## Registering the plugin

```php
use LaBoiteACode\FilamentActivityTimeline\FilamentActivityTimelinePlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        FilamentActivityTimelinePlugin::make(),
    ]);
}
```

The plugin works with sensible defaults and no further configuration.

## Quick start

This package does not record activity; it presents activity recorded by another
system. With `spatie/laravel-activitylog`, make your model log its changes, then
show the timeline.

1. Log activity on the model (see the Spatie documentation for all options):

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Order extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total', 'customer_id', 'paid_at'])
            ->logOnlyDirty();
    }
}
```

2. Show the timeline on the record's view page with the widget or the infolist
   entry (see [Basic usage](#basic-usage)).

That already gives a working timeline. Everything after that is about turning
those technical logs into clear, localized business sentences.

## Basic usage

### As a widget

Extend the widget to configure it, then register it on a resource record page.
On a record page Filament injects the current record into the widget's `$record`
property automatically.

```php
use App\Models\Order;
use LaBoiteACode\FilamentActivityTimeline\Timeline;
use LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget;

class OrderTimelineWidget extends ActivityTimelineWidget
{
    protected function timeline(): Timeline
    {
        return Timeline::make()
            ->source('spatie')
            ->heading('History')
            ->description('Everything that happened to this order.')
            ->limit(15)
            ->loadMore()
            ->filters();
    }
}
```

```php
// App\Filament\Resources\Orders\Pages\ViewOrder
protected function getFooterWidgets(): array
{
    return [
        OrderTimelineWidget::class,
    ];
}
```

Closures declared in `timeline()` run server side on every request and are never
serialized, so the subclass has access to the full API (custom sentences,
causer resolution, title formatting).

You can also drop the widget in with plain, serializable properties and let the
global registry provide the presentation:

```blade
@livewire(\LaBoiteACode\FilamentActivityTimeline\Widgets\ActivityTimelineWidget::class, [
    'record' => $record,
    'source' => 'spatie',
    'withLoadMore' => true,
    'withFilters' => true,
])
```

### In a page

`Timeline` is `Htmlable`, so it renders anywhere a view can echo it:

```php
use LaBoiteACode\FilamentActivityTimeline\Timeline;

Timeline::make('activity')
    ->record($this->record)
    ->loadMore()
    ->filters();
```

When rendered in a page, only serializable configuration crosses the Livewire
boundary, so declare closures on a widget subclass or in the global registry.

### In an infolist

Place the timeline in a resource infolist or any schema with
`ActivityTimelineEntry`. The record is provided by the infolist automatically.

```php
use LaBoiteACode\FilamentActivityTimeline\Infolists\ActivityTimelineEntry;

public function infolist(Schema $schema): Schema // Infolist $infolist on Filament 4
{
    return $schema->components([
        TextEntry::make('number'),
        // ...
        ActivityTimelineEntry::make('activity')
            ->source('spatie')
            ->heading('History')
            ->perPage(10)
            ->loadMore()
            ->filters(),
    ]);
}
```

The entry proxies the timeline configuration API: `source()`, `heading()`,
`description()`, `perPage()`, `loadMore()`, `filters()`, `modelLabel()`,
`attributes()`, `attributeLabels()`, `hiddenAttributes()`, `eventSentence()`,
`eventLabels()`, `eventIcons()`, `eventColors()` and `debugPresentation()`.

By default the entry is interactive: it embeds the timeline widget, so "load
more" and the filters work inside the infolist. Call `->static()` for a read
only, non interactive list (useful for print or a frozen infolist):

```php
ActivityTimelineEntry::make('activity')
    ->source('spatie')
    ->static();
```

### Drop-in widget properties

Every property accepted by the drop-in `@livewire()` call, all serializable:

| Property | Type | Purpose |
| --- | --- | --- |
| `record` | `Model` | The record whose activity is shown. |
| `source` | `string` | Source name (`spatie` by default). |
| `perPage` | `int` | Page size, also used as the "load more" step. |
| `withLoadMore` | `bool` | Show the "load more" button. |
| `withFilters` | `bool` | Show the event filter tabs. |
| `heading` / `description` | `string` | Section header texts. |
| `eventLabels` / `eventIcons` / `eventColors` | `array<string, string>` | Per event overrides, keyed by event name. |
| `presentationOverrides` | `array` | Serializable presentation overrides: `modelLabel`, `pluralModelLabel`, `attributeLabels`, `hiddenAttributes`, `eventSentences`. |
| `debug` | `bool` | Presentation diagnostics (never in production). |

## Using the semantic registry

Declare a model presentation once, usually in a service provider. Every timeline
that shows that model then benefits from it.

```php
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;

ActivityTimeline::forModel(Order::class)
    ->resource(OrderResource::class)
    ->label('order')
    ->pluralLabel('orders')
    ->recordTitleUsing(fn (Order $order) => $order->number)
    ->icon('heroicon-o-shopping-cart')
    ->color('primary')
    ->attributes([
        'status' => AttributePresentation::make('Status')
            ->enum(OrderStatus::class),

        'customer_id' => AttributePresentation::make('Customer')
            ->relationship('customer', titleAttribute: 'name'),

        'total' => AttributePresentation::make('Total')
            ->money('EUR'),

        'paid_at' => AttributePresentation::make('Payment date')
            ->dateTime(),

        'internal_token' => AttributePresentation::make()
            ->hidden(),
    ])
    ->eventSentence('created', ':causer created :subject.')
    ->eventSentence('updated', ':causer updated :subject.')
    ->eventSentence('deleted', ':causer deleted :subject.');
```

To start from a Filament resource (the resource provides the labels and the
record title attribute), use `forResource()`:

```php
ActivityTimeline::forResource(OrderResource::class)
    ->eventSentence('updated', ':causer updated :subject.');
```

A model may also expose its own presentation:

```php
use LaBoiteACode\FilamentActivityTimeline\Contracts\HasActivityTimelinePresentation;
use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;

class Order extends Model implements HasActivityTimelinePresentation
{
    public static function activityTimelinePresentation(): ModelPresentation
    {
        return ModelPresentation::make()
            ->label('order')
            ->recordTitleUsing(fn (Order $order): string => $order->number);
    }
}
```

Record titles resolve in this order: an explicit `recordTitleUsing()` callback,
the `ProvidesActivityTitle` contract on the model, `recordTitleAttributes()`,
then a snapshot stored on the activity.

Resolution priority for the whole presentation, highest first: local timeline
overrides, the global registry, the model contract, the Filament resource, then
conventions (humanized class and column names).

Groups of declarations can be packaged as presets: register a preset callback
with `ActivityTimeline::presentation()->registerPreset('name', $callback)` and
apply it with `ActivityTimeline::preset('name')`.

## Customizing events

Register a presentation for any event, custom or not:

```php
use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;

ActivityTimeline::event('invoice.sent')
    ->label('Invoice sent')
    ->sentence(':causer sent :subject to :recipient.')
    ->property('recipient', 'recipient_email')
    ->icon('heroicon-o-paper-airplane')
    ->color('info');
```

`property()` binds a sentence variable to a path inside the activity
properties, so business payloads stay addressable from the template.

Override an event locally on a timeline, or use a callback for full control:

```php
use LaBoiteACode\FilamentActivityTimeline\Support\PresentationContext;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineEntry;

Timeline::make()
    ->eventLabels(['payment.refunded' => 'Refunded'])
    ->eventIcons(['payment.refunded' => 'heroicon-o-arrow-uturn-left'])
    ->eventColors(['payment.refunded' => 'info'])
    ->eventSentence('updated', ':causer actualized :subject.')
    ->eventSentenceUsing(
        'status_changed',
        fn (TimelineEntry $entry, PresentationContext $context): string =>
            "{$context->causerName()} marked {$context->subject()} as {$context->newValue('status')}.",
    );
```

Available sentence variables: `:causer`, `:subject`, `:subject_label`,
`:subject_title`, `:event`, `:changes_count`, `:date` and `:property.path`.

Inside an `eventSentenceUsing()` callback, the `PresentationContext` exposes
`entry()`, `causerName()`, `subject()`, `subjectTitle()`, `subjectLabel()`,
`date()`, `newValue()`, `oldValue()`, `property()` and `changesCount()`.

The icon and color of the four Eloquent events (`created`, `updated`,
`deleted`, `restored`) are defined in the configuration file and can be changed
there globally.

## Customizing the causer

```php
Timeline::make()
    ->causerNameUsing(fn (?Model $causer) => $causer?->name)
    ->causerAvatarUsing(fn (?Model $causer) => $causer?->avatar_url);
```

When an activity has no causer, a configurable system identity is shown
(`system_causer` in the configuration, label translated through
`timeline.causer.system`).

## Displaying changes

For `updated` events, the old and new values are rendered readably. By default:

- sensitive attributes are hidden;
- column names are humanized;
- null, empty and boolean values are localized;
- long strings are truncated;
- relation identifiers are resolved to titles.

```php
Timeline::make()
    ->hiddenAttributes(['password', 'remember_token'])
    ->attributeLabels(['email_verified_at' => 'Email verification'])
    ->formatAttributeUsing('status', fn (mixed $value) => OrderStatus::tryFrom($value)?->getLabel() ?? $value);
```

Per attribute helpers, all declarable in the registry, on a timeline or on the
infolist entry:

```php
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;

AttributePresentation::make('Active')->boolean();                 // localized Yes / No, custom labels supported
AttributePresentation::make('Due date')->date();                  // date only, optional format
AttributePresentation::make('Published at')->dateTime();          // date and time, optional format
AttributePresentation::make('Amount')->money('EUR');              // localized currency, optional locale
AttributePresentation::make('Status')->enum(OrderStatus::class);  // enum label, HasLabel supported
AttributePresentation::make('Tags')->list();                      // arrays joined, custom glue
AttributePresentation::make('Metadata')->json();                  // compact JSON rendering
AttributePresentation::make('Priority')->map(['1' => 'Low', '2' => 'High']); // value to label map
AttributePresentation::make('Customer')->relationship('customer', titleAttribute: 'name');
AttributePresentation::make('Owner')->relationshipUsing(fn ($id) => User::find($id)?->full_name);
AttributePresentation::make('Custom')->formatUsing(fn (mixed $value) => strtoupper((string) $value));
AttributePresentation::make('Internal')->hidden();                // never shown
AttributePresentation::make('API key')->redacted();               // shown as a localized "Hidden"
AttributePresentation::make('Email')->maskUsing(fn (string $value) => Str::mask($value, '*', 3, -10));
```

### Snapshots

To keep a relation or subject title readable after the related record is
deleted, store a snapshot in the activity properties at log time:

```php
'presentation' => [
    'subject_label' => 'order',
    'subject_title' => 'CMD-2026-0184',
    'attributes' => [
        'customer_id' => ['old_label' => 'ACME France', 'new_label' => 'Dupont Conseil'],
    ],
]
```

Snapshots always win over a live lookup.

### Diagnosing the presentation

When you are not sure how a label, a record title or a format was resolved,
enable the diagnostic output. Keep it off in production.

```php
Timeline::make()->debugPresentation();
```

## Filters

Server side filters by event are enabled with `->filters()`. A native Filament
tab bar lets the user switch between all events and each known event.

```php
Timeline::make()->filters();                       // tabs for every known event
Timeline::make()->filters(['created', 'updated']); // tabs for these events only
```

Without an explicit list, the tabs are built from the events declared in the
configuration plus every event registered through `ActivityTimeline::event()`.
Filtering happens in the source query, not in the browser, so it stays exact on
large histories.

## Pagination

Set the page size with `->limit()` and enable progressive loading with
`->loadMore()`. The "load more" button is disabled while loading, disappears
when there is nothing left, keeps the already loaded items and never duplicates
an entry. Cursor pagination is used whenever the source supports it, so large
histories never load entirely into memory.

## Theming

The widget is designed to disappear into your panel:

- Generic UI (section, tabs, badges, avatars, buttons, empty state) uses native
  Filament Blade components.
- The timeline specific styles (rail, event dots, change chips) reuse Filament
  core color recipes, variable for variable. There is not a single hard coded
  color in the stylesheet, so a custom theme restyles the timeline
  automatically, dark mode included.
- Event dots are scoped with Filament's own `fi-color-{name}` classes. A color
  registered in your panel is therefore directly usable for a custom event:

```php
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

FilamentColor::register(['purple' => Color::Purple]);

ActivityTimeline::event('vip.upgraded')->color('purple');
```

- Every element carries a stable `fi-at-*` class (`fi-at-entry`, `fi-at-dot`,
  `fi-at-chip`, `fi-at-changes`, ...) for targeted CSS overrides in your theme.

## Translations

English and French ship with the package. Publish and edit them, or add your own
locale, with the `filament-activity-timeline-translations` tag.

Everything user visible is translatable: the section heading, event labels,
default sentences per event, the causer fallbacks, null and boolean values, the
change counters, the filter labels, the "load more" button, the empty state and
the error state. Visible labels are resolved from the translation files, so the
published configuration stays free of hard coded strings.

## Configuration

Publish `config/filament-activity-timeline.php` to change the defaults:

- `default_source`: the source used when a timeline does not pick one.
- `pagination.per_page` and `pagination.mode` (`load_more` or `simple`).
- `date_format` and `timezone` for the absolute date shown on hover.
- `system_causer`: the identity shown when an activity has no causer.
- `events`: the icon and color for each known event.
- `hidden_attributes`: attribute names that are never shown in a change list.
- `attributes.truncate`: the maximum length of a rendered string value.
- `relations.resolve`: resolve relation identifiers to titles.
- `debug`: presentation diagnostics, never in production.

## Adding a source

The Spatie source is registered as `spatie` by default. Register another source
(a custom table, an external API, another package) by name in a service
provider:

```php
use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;

ActivityTimeline::registerSource('audit', fn () => new AuditSource());
```

A source implements
`LaBoiteACode\FilamentActivityTimeline\Contracts\ActivitySource`: it is scoped
with `forRecord()`, filtered with `events()`, ordered with `latestFirst()` and
read with `paginate()`, returning normalized `TimelineEntry` objects inside a
`TimelineResult`. The presentation layer never depends on a specific logging
library. Select it with `->source('audit')`.

## Testing

```bash
composer test
```

The suite runs against Filament 4 and 5, Livewire 3 and 4, Laravel 12 and 13,
and PHP 8.3 to 8.5.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

Please see [SECURITY.md](SECURITY.md) for reporting vulnerabilities.

## Credits

- [Alexandre Ribes](https://alexandre-ribes.fr)
- [La Boite A Code](https://laboiteacode.fr)

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.
