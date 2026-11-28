<?php

namespace Packstub\SessionReplay\Filament\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Packstub\SessionReplay\Models\ReplayModel;

/**
 * A filter combination someone saved on the recordings list, theirs alone.
 *
 * @property int $id
 * @property string $owner_type
 * @property string $owner_id
 * @property string $name
 * @property array<string, mixed> $filters
 */
class SavedFilter extends ReplayModel
{
    protected $table = 'replay_saved_filters';

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    /** @param  Builder<self>  $query */
    public function scopeOwnedBy(Builder $query, Model $owner): void
    {
        $query->where('owner_type', $owner->getMorphClass())->where('owner_id', (string) $owner->getKey());
    }
}
