# Architecture

This document records the structural decisions behind the package. The guiding
idea is that the timeline is only the surface: the real value is a reusable
layer that turns technical activity into localized business events.

## Layers

```text
Source (Spatie, custom, ...)  ->  TimelineEntry (normalized, immutable)
                                        |
                             PresentationResolver
              (label, record title, causer, event, sentence, changes)
                                        |
                              RenderedEntry (ready to display)
                                        |
                         ActivityTimelineWidget + Blade (Filament native)
```

### Sources

`Contracts\ActivitySource` describes a read only, record scoped, filterable and
paginated source. `Sources\SpatieActivitySource` is the first implementation. A
source never returns a framework model to the presentation layer; it returns
`Data\TimelineEntry` objects. New sources are registered by name in
`Registries\SourceRegistry` and selected with `->source('name')`.

Cursor pagination is used when the store supports it. The widget grows a visible
window (the page size increases on "load more"), which keeps already loaded
items in place, avoids duplicates and never loads the whole history into memory.

### Normalized data

`Data\TimelineEntry` is the single immutable shape every source produces. It
carries the raw `properties`, the (possibly null) causer and subject, and the
subject type and id so a deleted subject can still be presented from a snapshot.
`Data\ChangeSet` is a defensive view over the `old` and `attributes` arrays that
never assumes a shape without checking it, so malformed properties can never
crash a render.

### Semantic presentation

This is the differentiating layer.

- `Presentation\ModelPresentation` declares, per model: labels, the record
  title, an icon, a color, attribute presentations and event sentences.
- `Presentation\AttributePresentation` declares, per attribute: a label, a
  format (text, boolean, date, date time, money, enum, list, json, map,
  relationship, custom) and sensitivity (hidden, redacted, masked).
- `Support\ValueFormatter` turns a raw value into a safe, readable string. It is
  free of database access and therefore trivial to unit test.
- `Support\RelationResolver` resolves relation identifiers to titles, grouped by
  model and cached for the render, so a timeline never runs one query per row.
- `Support\EventSentenceRenderer` fills sentence templates. Values are inserted
  as plain text and escaped by Blade on output.
- `Support\PresentationResolver` ties everything together and produces a
  `Data\RenderedEntry` per entry.

### Resolution priority

For a model, the effective presentation is merged from lowest to highest
priority, the highest winning:

1. local overrides on the `Timeline`;
2. the global registry (`ActivityTimeline::forModel()`);
3. the model contract (`HasActivityTimelinePresentation`);
4. the Filament resource (labels and title attribute), when configured;
5. conventions (humanized class name, record title attributes).

The record title follows the same idea: explicit callback, then the
`ProvidesActivityTitle` contract, then configured attributes, then a snapshot
stored on the activity.

## Rendering and the serialization boundary

`Timeline` is a declarative, typed configuration object. It is built fresh on
every request, so closures declared on it never cross a Livewire serialization
boundary.

`Widgets\ActivityTimelineWidget` is the interactive host. Being a Filament
widget, it is a Livewire component. It stores only serializable state: the
record, the source name, the active filter and the size of the visible window.
There are two ways to configure it:

- **Subclass** and override `timeline()` for full power, including closures,
  which run server side on every request and are never serialized.
- **Drop in** with plain properties and rely on the global registry for the
  presentation. `Timeline` is `Htmlable` and renders to the widget with only
  serializable properties.

`Infolists\ActivityTimelineEntry` places the timeline in a Filament infolist or
schema. It extends `Filament\Schemas\Components\Component` (shared by Filament 4
and 5). By default it embeds the interactive widget, so load more and filters
keep working inside the infolist; `static()` renders a read only list instead,
without a nested Livewire component. Both the widget and the static entry share
`Support\TimelineRenderer`, which builds the resolver, reads a page from the
source and renders it, so the wiring lives in one place.

## Filament 4 and 5, Livewire 3 and 4

The package supports two Filament major versions at once. To stay compatible:

- Generic UI (card, tabs, badges, avatars, buttons, empty state, callout) uses
  native Filament Blade components, so it inherits Filament's palette, default
  colors and dark mode in both majors.
- Only the timeline specific scaffolding (the rail, the event dots, the change
  chips) ships as a small, build free stylesheet under the package's own
  `fi-at-*` class namespace (`fi-ta-*` belongs to Filament's tables and must
  not be reused). Every color recombines a Filament core recipe, variable for
  variable: dots follow the modal icon tinted circle recipe scoped by the core
  `.fi-color-{name}` custom properties, chips follow the badge gray recipe, and
  rails and panels use the section hairline pair. Filament v4 exposes complete
  CSS colors (oklch), so tints are derived with `color-mix()` and the
  stylesheet contains zero color literals.
- The Livewire component sticks to the stable API shared by Livewire 3 and 4
  (public properties, `mount`, action methods, `wire:click`), avoiding anything
  that differs between the two. Two Blade and Livewire constraints are enforced
  by tests: directives such as `@js()` are never compiled inside component tag
  attributes (values are inlined with echoes instead), and view data keys never
  collide with public property names, because Livewire shares public properties
  with the view after the explicit view data.

## Testing note

Filament registers a global Livewire component hook. Outside an HTTP request the
shared instance Livewire uses for its `DataStore` can be dropped, which would
make component rendering fail. The test case rebinds the `DataStore` as a
singleton, which mirrors what a real request provides. This concerns the test
environment only; nothing in the shipped package depends on it.

## Out of scope for the MVP

Automatic semantic event detection, full relation snapshots, advanced batch
grouping, full resource component discovery, a template engine and business
presets are deliberately left for after the MVP. The data shapes are designed so
they can be added later without breaking the entry contract.
