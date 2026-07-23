<div class="fi-at-changes">
    <p class="fi-at-changes-count">
        {{ trans_choice('filament-activity-timeline::timeline.changes.count', count($changes), ['count' => count($changes)]) }}
    </p>

    <ul role="list" class="fi-at-changes-list">
        @foreach ($changes as $change)
            @php
                $accessible = $change->hasOld
                    ? __('filament-activity-timeline::timeline.changes.from_to', ['old' => $change->old, 'new' => $change->new])
                    : __('filament-activity-timeline::timeline.changes.set_to', ['new' => $change->new]);
            @endphp

            <li class="fi-at-change">
                <span class="fi-at-change-label">{{ $change->label }}</span>

                <span class="fi-at-change-values" aria-label="{{ $accessible }}">
                    @if ($change->hasOld)
                        <span class="fi-at-chip fi-at-chip--old">{{ $change->old }}</span>
                        <x-filament::icon icon="heroicon-m-arrow-right" class="fi-at-arrow" aria-hidden="true" />
                    @endif

                    <span class="fi-at-chip fi-at-chip--new">{{ $change->new }}</span>
                </span>
            </li>
        @endforeach
    </ul>
</div>
