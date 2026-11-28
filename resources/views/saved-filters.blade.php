@if ($savedFilters->isNotEmpty())
    <div class="fsr-saved-filters" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
        @foreach ($savedFilters as $saved)
            <div style="display: inline-flex; align-items: center; gap: 0.125rem;" wire:key="saved-filter-{{ $saved->getKey() }}">
                <x-filament::button
                    size="sm"
                    :color="$active === $saved->getKey() ? 'primary' : 'gray'"
                    :outlined="$active !== $saved->getKey()"
                    icon="heroicon-m-funnel"
                    wire:click="applySavedFilter({{ $saved->getKey() }})"
                >
                    {{ $saved->name }}
                </x-filament::button>

                <x-filament::icon-button
                    icon="heroicon-m-x-mark"
                    size="sm"
                    color="gray"
                    :label="__('Delete :name', ['name' => $saved->name])"
                    wire:click="mountAction('deleteSavedFilter', { id: {{ $saved->getKey() }} })"
                />
            </div>
        @endforeach
    </div>
@endif
