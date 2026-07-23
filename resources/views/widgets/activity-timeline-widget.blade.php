<div class="fi-at">
    <x-filament::section
        :heading="$sectionHeading"
        :description="filled($sectionDescription) ? $sectionDescription : null"
        icon="heroicon-o-clock"
    >
        @if ($showFilters)
            @include('filament-activity-timeline::components.filters', ['options' => $filterOptions])
        @endif

        @if ($errored)
            @include('filament-activity-timeline::components.error')
        @elseif (count($renderedEntries) === 0)
            @include('filament-activity-timeline::components.empty-state')
        @else
            @include('filament-activity-timeline::components.timeline', [
                'renderedEntries' => $renderedEntries,
                'debug' => $showDebug,
            ])

            @if ($hasMore)
                <div class="fi-at-load-more">
                    <x-filament::button
                        color="gray"
                        outlined
                        size="sm"
                        icon="heroicon-m-arrow-down"
                        wire:click="loadMore"
                        wire:target="loadMore"
                    >
                        {{ __('filament-activity-timeline::timeline.actions.load_more') }}
                    </x-filament::button>
                </div>
            @endif
        @endif
    </x-filament::section>
</div>
