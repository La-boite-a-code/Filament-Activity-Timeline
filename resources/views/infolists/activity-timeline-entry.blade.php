@if ($activityTimeline->isInteractive())
    @livewire(
        $activityTimeline->getWidgetClass(),
        $activityTimeline->getWidgetProps(),
        key($activityTimeline->getComponentKey())
    )
@else
    @php
        $renderedEntries = $activityTimeline->getStaticEntries();
    @endphp

    <div class="fi-ta">
        <x-filament::section
            :heading="$activityTimeline->getStaticHeading()"
            :description="$activityTimeline->getStaticDescription()"
            icon="heroicon-o-clock"
        >
            @if (count($renderedEntries) === 0)
                @include('filament-activity-timeline::components.empty-state')
            @else
                @include('filament-activity-timeline::components.timeline', [
                    'renderedEntries' => $renderedEntries,
                    'debug' => $activityTimeline->isDebug(),
                ])
            @endif
        </x-filament::section>
    </div>
@endif
