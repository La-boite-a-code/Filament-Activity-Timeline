<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\Tests\TestCase;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class)->in(__DIR__.'/Unit', __DIR__.'/Feature');

/**
 * Insert a raw activity row, bypassing the model events so tests control the
 * exact shape of the properties.
 *
 * @param  array<string, mixed>  $properties
 */
function makeActivity(
    string $event = 'updated',
    array $properties = [],
    ?Model $subject = null,
    ?Model $causer = null,
    ?string $description = null,
    ?string $batchUuid = null,
): Activity {
    $activity = new Activity;
    $activity->log_name = 'default';
    $activity->description = $description ?? $event;
    $activity->event = $event;
    $activity->properties = collect($properties);

    if ($subject !== null) {
        $activity->subject()->associate($subject);
    }

    if ($causer !== null) {
        $activity->causer()->associate($causer);
    }

    $activity->save();

    return $activity;
}
