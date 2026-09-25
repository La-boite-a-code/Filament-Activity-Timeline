<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\Tests\TestCase;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');

/**
 * Whether the installed activitylog stores a model's changes in their own
 * attribute_changes column (v5) rather than inside the properties (v4).
 */
function activitylogStoresChangesApart(): bool
{
    return method_exists('Spatie\Activitylog\Support\ActivityLogger', 'withChanges');
}

/**
 * Log a change through activitylog's own logger, writing it wherever the
 * installed version stores it, as LogsActivity does for a model event.
 *
 * @param  array<string, mixed>  $changes
 */
function logChanges(Model $subject, array $changes, string $event = 'updated', ?Model $causer = null): Activity
{
    $logger = activity()->performedOn($subject)->causedBy($causer)->event($event);

    $logger = activitylogStoresChangesApart()
        ? $logger->withChanges($changes)
        : $logger->withProperties($changes);

    /** @var Activity */
    return $logger->log($event);
}

/**
 * Insert a raw activity row, bypassing the model events so tests control the
 * exact shape of the properties. The attribute changes only exist on v5.
 *
 * @param  array<string, mixed>  $properties
 * @param  array<string, mixed>|null  $attributeChanges
 */
function makeActivity(
    string $event = 'updated',
    array $properties = [],
    ?Model $subject = null,
    ?Model $causer = null,
    ?string $description = null,
    ?string $batchUuid = null,
    ?array $attributeChanges = null,
): Activity {
    $activity = new Activity;
    $activity->log_name = 'default';
    $activity->description = $description ?? $event;
    $activity->event = $event;
    $activity->properties = collect($properties);

    if ($attributeChanges !== null) {
        $activity->setAttribute('attribute_changes', collect($attributeChanges));
    }

    if ($subject !== null) {
        $activity->subject()->associate($subject);
    }

    if ($causer !== null) {
        $activity->causer()->associate($causer);
    }

    $activity->save();

    return $activity;
}
