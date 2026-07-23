# Changelog

All notable changes to `filament-activity-timeline` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[0.1.0]: https://github.com/la-boite-a-code/filament-activity-timeline/releases/tag/v0.1.0
