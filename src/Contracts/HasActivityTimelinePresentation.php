<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Contracts;

use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;

/**
 * Optional contract a model may implement to expose its own presentation. It is
 * intentionally optional so the package never forces an interface onto every
 * model in an application.
 */
interface HasActivityTimelinePresentation
{
    public static function activityTimelinePresentation(): ModelPresentation;
}
