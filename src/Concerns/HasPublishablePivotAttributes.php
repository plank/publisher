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

    public function getDraftPivotAttributes(): array
    {
        return $this->{$this->pivotDraftColumn()} ?? [];
    }

    public function setDraftPivotAttributes(array $attributes): void
    {
        $draft = $this->{$this->pivotDraftColumn()} ?? [];

        foreach ($attributes as $key => $value) {
            if ($this->isExcludedFromPivotDraft($key)) {
                continue;
            }

            $draft[$key] = $value;
        }

        $this->{$this->pivotDraftColumn()} = $draft;
    }

    public function publishPivotAttributes(): void
    {
        $draft = $this->{$this->pivotDraftColumn()};

        if (! is_array($draft) || empty($draft)) {
            return;
        }

        foreach ($draft as $key => $value) {
            $this->attributes[$key] = $value;
        }

        $this->{$this->pivotDraftColumn()} = null;
    }

    public function revertPivotAttributes(): void
    {
        $this->{$this->pivotDraftColumn()} = null;
    }

    public function pivotDraftColumn(): string
    {
        return config()->get('publisher.columns.draft', 'draft');
    }

    public function isExcludedFromPivotDraft(string $key): bool
    {
        return in_array($key, $this->excludedFromPivotDraftByDefault());
    }

    protected function excludedFromPivotDraftByDefault(): array
    {
        $excluded = [
            'id',
            $this->pivotDraftColumn(),
            config()->get('publisher.columns.has_been_published', 'has_been_published'),
            config()->get('publisher.columns.should_delete', 'should_delete'),
        ];

        if ($this->getCreatedAtColumn()) {
            $excluded[] = $this->getCreatedAtColumn();
        }

        if ($this->getUpdatedAtColumn()) {
            $excluded[] = $this->getUpdatedAtColumn();
        }

        // Add foreign key columns
        if (property_exists($this, 'foreignKey') && $this->foreignKey) {
            $excluded[] = $this->foreignKey;
        }

        if (property_exists($this, 'relatedKey') && $this->relatedKey) {
            $excluded[] = $this->relatedKey;
        }

        return $excluded;
    }
}
