@php
    $color = $rendered->color ?? 'gray';
@endphp

<li class="fi-ta-entry" wire:key="fi-ta-entry-{{ $rendered->key }}">
    <span class="fi-ta-line" aria-hidden="true"></span>

    <span class="fi-ta-dot fi-ta-dot--{{ $color }}" aria-hidden="true">
        @if (filled($rendered->icon))
            <x-filament::icon :icon="$rendered->icon" />
        @endif
    </span>

    <div class="fi-ta-body">
        <p class="fi-ta-sentence">{{ $rendered->sentence }}</p>

        <div class="fi-ta-meta">
            @if ($rendered->hasCauserAvatar())
                <x-filament::avatar
                    :src="$rendered->causerAvatar"
                    :alt="$rendered->causerName"
                    size="sm"
                    class="fi-ta-avatar"
                />
            @endif

            <span class="fi-ta-causer">{{ $rendered->causerName }}</span>
            <span class="fi-ta-sep" aria-hidden="true">&middot;</span>
            <time
                datetime="{{ $rendered->entry->occurredAt->toIso8601String() }}"
                title="{{ $rendered->absoluteDate }}"
            >{{ $rendered->relativeDate }}</time>

            <x-filament::badge :color="$color" size="sm">
                {{ $rendered->eventLabel }}
            </x-filament::badge>
        </div>

        @if (filled($rendered->description))
            <p class="fi-ta-entry-description">{{ $rendered->description }}</p>
        @endif

        @if ($rendered->hasChanges())
            @include('filament-activity-timeline::components.changes', ['changes' => $rendered->changes])
        @endif

        @if ($debug && $rendered->debug !== null)
            @include('filament-activity-timeline::components.debug', ['debug' => $rendered->debug])
        @endif
    </div>
</li>
