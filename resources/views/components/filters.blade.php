<x-filament::tabs :label="__('filament-activity-timeline::timeline.filters.label')" class="fi-ta-filters">
    @foreach ($options as $option)
        <x-filament::tabs.item
            :active="$option['active']"
            wire:click="filterByEvent(@js($option['value']))"
            wire:key="fi-ta-filter-{{ $option['value'] ?? 'all' }}"
        >
            {{ $option['label'] }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>
