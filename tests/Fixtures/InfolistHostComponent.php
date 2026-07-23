<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Tests\Fixtures;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use LaBoiteACode\FilamentActivityTimeline\Infolists\ActivityTimelineEntry;
use Livewire\Component;

class InfolistHostComponent extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?Model $record = null;

    public bool $useStatic = false;

    public function activity(Schema $schema): Schema
    {
        $entry = ActivityTimelineEntry::make('activity')
            ->source('spatie')
            ->loadMore()
            ->filters();

        if ($this->useStatic) {
            $entry->static();
        }

        return $schema
            ->record($this->record)
            ->components([$entry]);
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>{{ $this->getSchema('activity') }}</div>
        BLADE;
    }
}
