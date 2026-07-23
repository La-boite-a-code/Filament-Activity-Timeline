<ol role="list" class="fi-at-list">
    @foreach ($renderedEntries as $rendered)
        @include('filament-activity-timeline::components.timeline-entry', [
            'rendered' => $rendered,
            'debug' => $debug,
        ])
    @endforeach
</ol>
