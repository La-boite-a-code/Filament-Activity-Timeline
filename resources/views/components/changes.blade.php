<div class="fi-ta-changes">
    <p class="fi-ta-changes-count">
        {{ trans_choice('filament-activity-timeline::timeline.changes.count', count($changes), ['count' => count($changes)]) }}
    </p>

    <ul role="list" class="fi-ta-changes-list">
        @foreach ($changes as $change)
            @php
                $accessible = $change->hasOld
                    ? __('filament-activity-timeline::timeline.changes.from_to', ['old' => $change->old, 'new' => $change->new])
                    : __('filament-activity-timeline::timeline.changes.set_to', ['new' => $change->new]);
            @endphp

            <li class="fi-ta-change">
                <span class="fi-ta-change-label">{{ $change->label }}</span>

                <span class="fi-ta-change-values" aria-label="{{ $accessible }}">
                    @if ($change->hasOld)
                        <x-filament::badge color="gray" size="sm">{{ $change->old }}</x-filament::badge>
                        <x-filament::icon icon="heroicon-m-arrow-right" class="fi-ta-arrow" aria-hidden="true" />
                    @endif

                    <x-filament::badge color="primary" size="sm">{{ $change->new }}</x-filament::badge>
                </span>
            </li>
        @endforeach
    </ul>
</div>
