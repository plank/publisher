<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Exceptions\DraftException;
use Plank\Publisher\Facades\Publisher;

/**
 * @mixin FiresPublishingEvents
 * @mixin Publishable
 */
trait HasPublishableAttributes
{
    public static function bootHasPublishableAttributes()
    {
        static::retrieved(function (Publishable&Model $model) {
            if ($model->shouldLoadFromDraft()) {
                $column = $model->draftColumn();

                if ($model->{$column} === null) {
                    throw new DraftException('Attempting to load content from `'.$column.'` but it has not been set. [model: '.get_class($model).'][id: '.$model->getKey().'].');
                }

                $model->syncAttributesFromDraft();
            }
        });

        static::publishing(function (Publishable&Model $model) {
            $model->{$model->hasBeenPublishedColumn()} = true;
        });

        static::drafting(function (Publishable&Model $model) {
            $model->putAttributesInDraft();
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
            if ($this->ignoreCounts() && $this->isCount($key)) {
                continue;
            }

            if ($this->ignoreSums() && $this->isSum($key)) {
                continue;
            }

            if (! in_array($key, $excluded)) {
                $draft[$key] = $value;
            }
        }

        return $draft;
    }

    protected function ignoreCounts()
    {
        if (property_exists($this, 'ignoreCounts')) {
            return (bool) $this->ignoreCounts;
        }

        return true;
    }

    protected function isCount(string $key): bool
    {
        $key = str($key);

        if (! $key->endsWith('_count')) {
            return false;
        }

        $relationship = $key->before('_count');

        return method_exists($this, $relationship);
    }

    protected function ignoreSums()
    {
        if (property_exists($this, 'ignoreSums')) {
            return (bool) $this->ignoreSums;
        }

        return true;
    }

    protected function isSum(string $key): bool
    {
        $key = str($key);

        if (! $key->contains('_sum_')) {
            return false;
        }

        $relationship = $key->before('_sum_');

        return method_exists($this, $relationship);
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

            $this->original[$key] = ! $value;
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
            $this->shouldDeleteColumn(),
            $this->dependsOnPublishableForeignKey(),
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
}
