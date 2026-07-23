<?php

declare(strict_types=1);

return [

    'heading' => 'Activity',

    'events' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ],

    // Default business sentences. Available variables:
    // :causer :subject :subject_label :subject_title :event :changes_count :date
    'sentences' => [
        'created' => ':causer created :subject.',
        'updated' => ':causer updated :subject.',
        'deleted' => ':causer deleted :subject.',
        'restored' => ':causer restored :subject.',
        'default' => ':causer ran ":event" on :subject.',
    ],

    'causer' => [
        'system' => 'System',
        'unknown' => 'Someone',
    ],

    'subject' => [
        'unknown' => 'a record',
        'fallback' => ':label #:id',
    ],

    'changes' => [
        'heading' => 'Changes',
        'count' => '{0} No change|{1} :count change|[2,*] :count changes',
        'from_to' => ':old to :new',
        'set_to' => 'set to :new',
        'cleared' => 'cleared',
    ],

    'values' => [
        'null' => 'Not set',
        'empty' => 'Empty',
        'true' => 'Yes',
        'false' => 'No',
        'list_empty' => 'None',
        'redacted' => 'Hidden',
    ],

    'filters' => [
        'label' => 'Filter activity',
        'all' => 'All events',
    ],

    'actions' => [
        'load_more' => 'Load more',
        'loading' => 'Loading',
    ],

    'empty' => [
        'heading' => 'No activity yet',
        'description' => 'Actions performed on this record will appear here.',
    ],

    'error' => [
        'heading' => 'Unable to load activity',
        'description' => 'The activity source could not be read.',
    ],

    'meta' => [
        'occurred_at' => 'Occurred at :date',
    ],

    'debug' => [
        'heading' => 'Presentation diagnostics',
        'model_label' => 'Model label',
        'record_title' => 'Record title',
        'source' => 'Source',
        'renderer' => 'Sentence renderer',
        'properties' => 'Available properties',
    ],

];
