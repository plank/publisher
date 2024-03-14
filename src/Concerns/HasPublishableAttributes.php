<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Facades\Publisher;

/**
 * @mixin FiresPublishingEvents
 * @mixin Publishable
 */
trait HasPublishableAttributes
{
    public function initializeHasPublishableAttributes()
    {
        $this->mergeCasts([
            $this->draftColumn() => 'json',
            $this->hasBeenPublishedColumn() => 'boolean',
        ]);

        $this->makeHidden($this->draftColumn());

        $this->attributes[$this->hasBeenPublishedColumn()] ??= false;
    }

    public function draftColumn(): string
    {
        return config('publisher.columns.draft', 'draft');
    }

    public static function bootHasPublishableAttributes()
    {
        static::retrieved(function (Publishable&Model $model) {
            if (Publisher::draftContentAllowed() && $model->isNotPublished()) {
                $model->syncAttributesFromDraft();
            }
        });

        static::publishing(function (Publishable&Model $model) {
            $model->{$model->hasBeenPublishedColumn()} = true;
        });

        static::drafting(function (Publishable&Model $model) {
            $model->putAttributesInDraft();
            $model->{$model->workflowColumn()} = $model->unpublishedState();
        });

        static::drafted(function (Publishable&Model $model) {
            $model->syncAttributesFromDraft();
        });

        static::undrafting(function (Publishable&Model $model) {
            $model->publishAttributes();
        });
    }

    public function syncAttributesFromDraft(): void
    {
        foreach ($this->{$this->draftColumn()} as $key => $value) {
            $this->attributes[$key] = $value;
            $this->syncOriginalAttribute($key);
        }
    }

    public function putAttributesInDraft(): void
    {
        $draft = [];

        foreach ($this->attributesForDraft() as $key => $value) {
            $draft[$key] = $value;
        }

        $this->{$this->draftColumn()} = $draft;

        foreach ($this->getDirtyKeys() as $key) {
            if ($this->isExcludedFromDraft($key)) {
                continue;
            }

            if (isset($this->original[$key])) {
                $this->attributes[$key] = $this->original[$key];
            }
        }
    }

    protected function attributesForDraft(): array
    {
        $draft = [];

        $excluded = array_merge(
            $this->excludedFromDraftByDefault(),
            $this->excludedFromDraft()
        );

        foreach ($this->attributes as $key => $value) {
            if (! in_array($key, $excluded)) {
                $draft[$key] = $value;
            }
        }

        return $draft;
    }

    protected function getDirtyKeys()
    {
        return array_keys($this->getDirty());
    }

    public function publishAttributes(): void
    {
        foreach ($this->getDraftAttributes() as $key => $value) {
            if ($this->isDirty($key)) {
                continue;
            }

            $this->original[$key] = !$value;
        }

        $this->attributes[$this->draftColumn()] = null;
    }

    protected function getDraftAttributes(): array
    {
        return $this->{$this->draftColumn()} ?? [];
    }

    public function isExcludedFromDraft(string $key): bool
    {
        if (in_array($key, $this->excludedFromDraftByDefault())) {
            return true;
        }

        return in_array($key, $this->excludedFromDraft());
    }

    protected function excludedFromDraftByDefault(): array
    {
        $attributes = array_filter([
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
            $this->workflowColumn(),
            $this->draftColumn(),
            $this->hasBeenPublishedColumn(),
        ]);

        if (in_array(SoftDeletes::class, class_uses_recursive($this))) {
            $attributes[] = $this->getDeletedAtColumn();
        }

        $attributes = array_merge($attributes, $this->qualifyColumns($attributes));

        return $attributes;
    }

    protected function excludedFromDraft(): array
    {
        return [];
    }

    public function hasDirtyDraftableAttributes(): bool
    {
        return ! empty($this->getDirtyDraftableAttributes());
    }

    public function getDirtyDraftableAttributes(): array
    {
        $excluded = array_merge(
            $this->excludedFromDraftByDefault(),
            $this->excludedFromDraft()
        );

        return array_diff_key($this->getDirty(), array_flip($excluded));
    }
}
