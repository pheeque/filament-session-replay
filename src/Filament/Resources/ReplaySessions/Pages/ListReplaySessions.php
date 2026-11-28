<?php

namespace Packstub\SessionReplay\Filament\Resources\ReplaySessions\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Packstub\SessionReplay\Filament\Assist\PanelAssist;
use Packstub\SessionReplay\Filament\Models\SavedFilter;
use Packstub\SessionReplay\Filament\Resources\ReplaySessions\ReplaySessionResource;

class ListReplaySessions extends ListRecords
{
    protected static string $resource = ReplaySessionResource::class;

    /** The saved filter applied last, until the filters change. */
    public ?int $savedFilter = null;

    /**
     * While Assist is on: every recording, and the ones live now (an upload or a heartbeat in the last two
     * minutes, the window the core's isLive() uses), where Offer help is.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        if (! PanelAssist::enabled()) {
            return [];
        }

        return [
            'all' => Tab::make(__('All')),
            'live' => Tab::make(__('Live now'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->live())
                ->badge(fn (): ?int => ($count = ReplaySessionResource::scope(ReplaySessionResource::getModel()::query())->live()->count()) > 0 ? $count : null)
                ->badgeColor('success'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getTabsContentComponent(),
            View::make('filament-session-replay::saved-filters')->viewData(fn (): array => ['savedFilters' => $this->savedFilters(), 'active' => $this->savedFilter]),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveFilters')
                ->label(__('Save filters'))
                ->icon(Heroicon::OutlinedBookmark)
                ->color('gray')
                ->visible(fn (): bool => $this->owner() !== null && $this->getTable()->getFilterIndicators() !== [])
                ->schema([TextInput::make('name')->label(__('Name'))->required()->maxLength(255)])
                ->action(function (array $data): void {
                    $owner = $this->owner();

                    $this->savedFilter = SavedFilter::query()->create([
                        'owner_type' => $owner->getMorphClass(),
                        'owner_id' => (string) $owner->getKey(),
                        'name' => $data['name'],
                        'filters' => $this->tableFilters ?? [],
                    ])->getKey();
                })
                ->successNotificationTitle(__('Filters saved')),
        ];
    }

    public function applySavedFilter(int $id): void
    {
        $saved = $this->savedFilters()->find($id);

        if ($saved === null) {
            return;
        }

        $this->getTableFiltersForm()->fill($saved->filters);

        $this->getTable()->hasDeferredFilters() ? $this->applyTableFilters() : $this->handleTableFilterUpdates();

        $this->savedFilter = $saved->getKey();
    }

    public function deleteSavedFilterAction(): Action
    {
        return Action::make('deleteSavedFilter')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => __('Delete ":name"?', ['name' => $this->savedFilters()->find($arguments['id'] ?? null)?->name]))
            ->color('danger')
            ->action(function (array $arguments): void {
                $this->savedFilters()->find($arguments['id'] ?? null)?->delete();

                if ($this->savedFilter === ($arguments['id'] ?? null)) {
                    $this->savedFilter = null;
                }
            });
    }

    protected function handleTableFilterUpdates(): void
    {
        parent::handleTableFilterUpdates();

        $this->savedFilter = null;
    }

    /** @return Collection<int, SavedFilter> */
    protected function savedFilters(): Collection
    {
        $owner = $this->owner();

        return $owner === null ? new Collection : SavedFilter::query()->ownedBy($owner)->orderBy('name')->get();
    }

    protected function owner(): ?Model
    {
        $user = Filament::auth()->user();

        return $user instanceof Model ? $user : null;
    }
}
