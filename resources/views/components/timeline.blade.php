<ol role="list" class="fi-ta-list">
    @foreach ($renderedEntries as $rendered)
        @include('filament-activity-timeline::components.timeline-entry', [
            'rendered' => $rendered,
            'debug' => $debug,
        ])
    @endforeach
</ol>
