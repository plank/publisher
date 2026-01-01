<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Plank\Publisher\Facades\Publisher;

/**
 * @mixin Pivot
 */
trait HasPublishablePivotAttributes
{
    public function initializeHasPublishablePivotAttributes(): void
    {
        $this->mergeCasts([
            $this->pivotDraftColumn() => 'json',
        ]);
    }

    public static function bootHasPublishablePivotAttributes(): void
    {
        static::retrieved(function (Pivot $pivot) {
            if ($pivot->shouldLoadPivotFromDraft()) {
                $pivot->syncPivotAttributesFromDraft();
            }
        });
    }

    public function shouldLoadPivotFromDraft(): bool
    {
        return Publisher::draftContentAllowed()
            && $this->hasDraftPivotAttributes();
    }

    public function hasDraftPivotAttributes(): bool
    {
        return ! empty($this->{$this->pivotDraftColumn()});
    }

    public function syncPivotAttributesFromDraft(): void
    {
        $draft = $this->{$this->pivotDraftColumn()};

        if (! is_array($draft)) {
            return;
        }

        foreach ($draft as $key => $value) {
            $this->attributes[$key] = $value;
            $this->syncOriginalAttribute($key);
        }
    }

    public function pivotDraftColumn(): string
    {
        return config()->get('publisher.columns.draft', 'draft');
    }
}
