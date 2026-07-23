# Changelog

All notable changes to `filament-activity-timeline` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.0]: https://github.com/la-boite-a-code/filament-activity-timeline/compare/v0.1.0...v1.0.0
[0.1.0]: https://github.com/la-boite-a-code/filament-activity-timeline/releases/tag/v0.1.0
