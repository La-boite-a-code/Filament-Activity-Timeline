<x-filament::tabs :label="__('filament-activity-timeline::timeline.filters.label')" class="fi-at-filters">
    {{-- @js() is never compiled inside component tag attributes: the value must
         be inlined with an echo. The empty string is normalized back to null
         (the "all events" filter) by ActivityTimelineWidget::filterByEvent(). --}}
    @foreach ($options as $option)
        <x-filament::tabs.item
            :active="$option['active']"
            wire:click="filterByEvent('{{ $option['value'] }}')"
            wire:key="fi-at-filter-{{ $option['value'] ?? 'all' }}"
        >
            {{ $option['label'] }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>
