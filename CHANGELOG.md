# Changelog

All notable changes to `filament-activity-timeline` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `ActivityTimelineWidget` now honours its column span. A page lays its header and footer widgets out on a grid (two columns by default) and Filament applies a widget's span through the `<x-filament-widgets::widget>` wrapper, which the widget view did not use: the `'full'` span never reached the page and the timeline only took half of its width. The view now renders inside that wrapper, so `$columnSpan` and `$columnStart` work as on any Filament widget ([#2](https://github.com/la-boite-a-code/filament-activity-timeline/issues/2)).
- With `spatie/laravel-activitylog` v5, the timeline showed no field changes. v5 records a model's changes in their own `attribute_changes` column, where v4 kept them inside `properties`, and the Spatie source only read `properties`. It now merges `attribute_changes` over `properties`, so both versions render the same changes, including activities logged before the upgrade ([#3](https://github.com/la-boite-a-code/filament-activity-timeline/issues/3)).
- With `spatie/laravel-activitylog` v5, an application that prevents accessing missing attributes (`Model::shouldBeStrict()`) rendered the error state instead of the timeline: v5 dropped the `batch_uuid` column the Spatie source read on every activity. The source now only reads a column the row carries, which also keeps the new `attribute_changes` read safe on v4.

## [1.0.2] - 2026-09-22

### Security

- Every public property of `ActivityTimelineWidget` is now `#[Locked]`. Livewire lets a browser write to any public property that is not locked, and none of this state is a user input: a crafted request could change the source, raise the page size, turn the presentation diagnostics on, or replace `presentationOverrides`. Replacing the overrides was the one with real reach, because it dropped the `hiddenAttributes` the widget was mounted with and revealed those attributes in the change lists. Attributes listed in the `hidden_attributes` configuration were never affected, and the record itself was never swappable (Livewire keeps the model identity in the checksummed snapshot), so the timeline always stayed scoped to the mounted record.
- The visible window is now bounded by `pagination.max_per_page` (500 by default). `loadMore()` grew the page size by one step per call with no ceiling, and the page size was writable from the browser, so a single request could ask the source for an unbounded number of rows with their `causer` and `subject` relations. A `per_page` configured above the ceiling is still honoured in full.
- `filterByEvent()` now only accepts an event the timeline actually offers as a filter tab, and falls back to the unfiltered timeline otherwise. The event was previously passed straight to the source. It was bound as a query parameter, so no injection was possible, and filtering only ever narrows an already visible result set, but the widget no longer queries an event it was not configured to expose.

### Fixed

- An authorization failure raised while reading activity is no longer swallowed. `readActivity()` caught every `Throwable` and rendered the generic error state, so a denied timeline looked like a record with no history and a `403` never reached the handler. `AuthorizationException`, `AuthenticationException`, `ValidationException`, `HttpResponseException` and any `HttpExceptionInterface` now propagate. Every other failure still degrades to the error state, and is still reported.
- `mount()` now clamps the configured page size to at least 1, so a `per_page` of `0` or a negative value no longer reaches the source.

### Added

- `pagination.max_per_page` configuration key. Published configuration files keep working without it: the ceiling falls back to 500.

## [1.0.1] - 2026-07-24

### Fixed

- Installing the package with the Spatie source on PHP 8.3 was impossible: the dev dependency and suggestion pinned `spatie/laravel-activitylog` to `^5.0`, which requires PHP 8.4. The constraint is now `^4.12|^5.0` and the test suite runs against both major versions.

## [1.0.0] - 2026-07-24

First stable release.

### Fixed

- Filter tabs were dead on click: the `@js()` directive is never compiled inside Blade component tag attributes, so the browser received a literal `@js(...)` expression and Alpine failed with a syntax error. The event value is now inlined with a standard echo.
- The section heading (and description) never rendered when not explicitly configured: Livewire shares public properties with the view after the explicit view data, so the null `$heading` property erased the resolved default. View data keys no longer collide with public property names.
- All CSS classes moved from the `fi-ta-*` prefix to `fi-at-*`: `fi-ta` is Filament's own table namespace, and core table styles (grid layout, paddings) were bleeding into the timeline, visibly breaking the filter tabs.
- Colors now use Filament v4 palette variables correctly: v4 exposes complete CSS colors (oklch), so the previous `rgb(var(--gray-200, ...))` declarations were invalid and silently dropped (invisible rail, unstyled dots).

### Changed

- Refined the timeline design: soft tinted event dots, hairline rail, aligned label column for changes, and quiet value chips (old value struck through, new value emphasized) instead of colored badges for every value.
- Every color is now a Filament CSS variable, recombining core recipes verbatim: dots follow the `.fi-modal-icon-bg` tinted circle recipe scoped by the core `.fi-color-{name}` custom properties (custom registered colors included), chips follow the `.fi-badge` gray recipe, and rails/panels use the `.fi-section` hairline pair. The stylesheet contains zero color literals.

## [0.1.0] - 2026-07-23

### Added

- Initial release of the semantic activity timeline for Filament.
- Reusable `Timeline` configuration object and `ActivityTimelineWidget`.
- `ActivityTimelineEntry` schema component to place the timeline in an infolist, interactive by default with an optional read only `static()` mode.
- `spatie/laravel-activitylog` source with cursor pagination and server side filters.
- Per model semantic registry (`ActivityTimeline::forModel()`): labels, record titles, icons, colors.
- Attribute presentation with text, boolean, date, date time, money, enum, list, json and map formats.
- Human readable null, empty and boolean values, all translatable.
- Sensitive attribute hiding, redaction and masking.
- Relation resolution with per render caching to prevent N+1 queries.
- Optional subject and relation snapshots to keep titles after deletion.
- Custom events and translatable business sentences per event.
- Resolution priority between local overrides, global registry, model contract, Filament resource and conventions.
- English and French translations.
- Light and dark themes, responsive and accessible markup.
- Support for Filament 4 and 5, Laravel 12 and 13, PHP 8.3 to 8.5.

[Unreleased]: https://github.com/la-boite-a-code/filament-activity-timeline/compare/v1.0.2...HEAD
[1.0.2]: https://github.com/la-boite-a-code/filament-activity-timeline/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/la-boite-a-code/filament-activity-timeline/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/la-boite-a-code/filament-activity-timeline/compare/v0.1.0...v1.0.0
[0.1.0]: https://github.com/la-boite-a-code/filament-activity-timeline/releases/tag/v0.1.0
