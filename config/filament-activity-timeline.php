<?php

declare(strict_types=1);

// Visible labels are resolved through the translation files so that the
// published configuration stays free of hard coded, single language strings.
// See resources/lang/{en,fr}/timeline.php to translate or override them.

return [

    /*
    |--------------------------------------------------------------------------
    | Default activity source
    |--------------------------------------------------------------------------
    |
    | The name of the source used when a timeline does not explicitly pick one.
    | The package ships with the "spatie" source. Register your own sources
    | through the SourceRegistry to add more.
    |
    */

    'default_source' => 'spatie',

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | "mode" may be "load_more" (a button appends the next page) or "simple"
    | (a single page is displayed). Cursor pagination is used when the source
    | supports it to keep large histories out of memory.
    |
    */

    'pagination' => [
        'per_page' => 20,
        'mode' => 'load_more',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date formatting
    |--------------------------------------------------------------------------
    |
    | "format" is the absolute date shown on hover and used as an accessible
    | title. "timezone" of null falls back to the application timezone.
    |
    */

    'date_format' => 'd/m/Y H:i',
    'timezone' => null,

    /*
    |--------------------------------------------------------------------------
    | System causer
    |--------------------------------------------------------------------------
    |
    | Identity displayed when an activity has no causer. Set "label" to null to
    | use the translated default (timeline.system_causer).
    |
    */

    'system_causer' => [
        'label' => null,
        'icon' => 'heroicon-o-cpu-chip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event presentation
    |--------------------------------------------------------------------------
    |
    | Icon and color for each known event. Labels are translated through
    | timeline.events.{event}. Unknown events fall back to a humanized name.
    |
    */

    'events' => [
        'created' => [
            'icon' => 'heroicon-o-plus',
            'color' => 'success',
        ],
        'updated' => [
            'icon' => 'heroicon-o-pencil-square',
            'color' => 'warning',
        ],
        'deleted' => [
            'icon' => 'heroicon-o-trash',
            'color' => 'danger',
        ],
        'restored' => [
            'icon' => 'heroicon-o-arrow-uturn-left',
            'color' => 'info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden attributes
    |--------------------------------------------------------------------------
    |
    | Attribute names that must never appear in a change list. Sensitive data
    | is masked by default; add your own columns here.
    |
    */

    'hidden_attributes' => [
        'password',
        'remember_token',
        'api_token',
        'secret',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute rendering
    |--------------------------------------------------------------------------
    |
    | "truncate" is the maximum length of a rendered string value before it is
    | shortened. Set to null to disable truncation.
    |
    */

    'attributes' => [
        'truncate' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Relation resolution
    |--------------------------------------------------------------------------
    |
    | When enabled, relation identifiers found in a change list are resolved to
    | a human readable title. Resolutions are grouped and cached per render to
    | avoid N+1 queries. Snapshots stored on the activity always win.
    |
    */

    'relations' => [
        'resolve' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Presentation diagnostics
    |--------------------------------------------------------------------------
    |
    | Enables the diagnostic output that explains how labels, titles, formats
    | and renderers were resolved. Never turn this on in production.
    |
    */

    'debug' => false,

];
