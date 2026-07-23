# Contributing

Thank you for considering a contribution. This document describes how to work
on the package locally.

## Requirements

- PHP 8.3, 8.4 or 8.5
- Composer

## Getting started

```bash
git clone https://github.com/la-boite-a-code/filament-activity-timeline
cd filament-activity-timeline
composer install
```

The package is developed and tested against two Filament major versions. To
switch the installed set of dependencies, change the constraints on the
command line and update:

```bash
# Filament 5 with Livewire 4 (default)
composer update --prefer-stable

# Filament 4 with Livewire 3
composer require "filament/filament:^4.0" "livewire/livewire:^3.5" --dev --with-all-dependencies
```

## Quality gates

Every pull request must pass the same checks the CI runs:

```bash
composer format     # Laravel Pint
composer analyse    # PHPStan / Larastan
composer test       # Pest
```

Please keep the following conventions:

- No em dash characters anywhere in the code, comments, tests or documentation.
- Strict types are declared in every PHP file.
- New behavior is covered by unit or Livewire tests.
- Visible strings live in the translation files, never hard coded in the config.
- Public API changes are documented in `CHANGELOG.md` under `Unreleased`.

## Reporting issues

Please include the Filament, Laravel and PHP versions, a minimal reproduction
and the expected versus actual output.
