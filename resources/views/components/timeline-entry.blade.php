@php
    $color = $rendered->color ?? 'gray';
@endphp

<li class="fi-at-entry" wire:key="fi-at-entry-{{ $rendered->key }}">
    <span class="fi-at-line" aria-hidden="true"></span>

    {{-- The core .fi-color-{name} classes scope Filament's own --color-*
         custom properties, so the dot follows the panel theme exactly.
         Like core components, gray is the unscoped default. --}}
    <span
        @class([
            'fi-at-dot',
            'fi-color' => $color !== 'gray',
            "fi-color-{$color}" => $color !== 'gray',
        ])
        aria-hidden="true"
    >
        @if (filled($rendered->icon))
            <x-filament::icon :icon="$rendered->icon" />
        @else
            <span class="fi-at-dot-core"></span>
        @endif
    </span>

    <div class="fi-at-body">
        <div class="fi-at-header">
            <p class="fi-at-sentence">{{ $rendered->sentence }}</p>

            <x-filament::badge :color="$color" size="sm" class="fi-at-event-badge">
                {{ $rendered->eventLabel }}
            </x-filament::badge>
        </div>

        <div class="fi-at-meta">
            @if ($rendered->hasCauserAvatar())
                <x-filament::avatar
                    :src="$rendered->causerAvatar"
                    :alt="$rendered->causerName"
                    size="sm"
                    class="fi-at-avatar"
                />
            @endif

            <span class="fi-at-causer">{{ $rendered->causerName }}</span>
            <span class="fi-at-sep" aria-hidden="true"></span>
            <time
                datetime="{{ $rendered->entry->occurredAt->toIso8601String() }}"
                title="{{ $rendered->absoluteDate }}"
            >{{ $rendered->relativeDate }}</time>
        </div>

        @if (filled($rendered->description))
            <p class="fi-at-entry-description">{{ $rendered->description }}</p>
        @endif

        @if ($rendered->hasChanges())
            @include('filament-activity-timeline::components.changes', ['changes' => $rendered->changes])
        @endif

        @if ($debug && $rendered->debug !== null)
            @include('filament-activity-timeline::components.debug', ['debug' => $rendered->debug])
        @endif
    </div>
</li>
