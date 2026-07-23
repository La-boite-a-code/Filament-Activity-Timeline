<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\Contracts\HasActivityTimelinePresentation;
use LaBoiteACode\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;

class Document extends Model implements HasActivityTimelinePresentation, ProvidesActivityTitle
{
    protected $table = 'orders';

    protected $guarded = [];

    public static function activityTimelinePresentation(): ModelPresentation
    {
        return ModelPresentation::make()
            ->label('document')
            ->pluralLabel('documents')
            ->icon('heroicon-o-document');
    }

    public function getActivityTimelineTitle(): ?string
    {
        return $this->getAttribute('number');
    }
}
