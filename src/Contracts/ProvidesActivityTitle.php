<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Contracts;

/**
 * Optional contract a model may implement to provide the business title used to
 * name one of its records inside a timeline, for example "CMD-2026-0184".
 */
interface ProvidesActivityTitle
{
    public function getActivityTimelineTitle(): ?string;
}
