<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Builders\PublisherBuilder;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Scopes\PublisherScope;

/**
 * @mixin Model
 *
 * @see Publishable
 */
trait IsPublishable
{
    use FiresPublishingEvents;
    use HasPublishableAttributes;

    public function initializeIsPublishable()
    {
        $this->attributes[$this->workflowColumn()] ??= $this->unpublishedState();
    }

    public static function bootIsPublishable()
    {
        static::addGlobalScope(new PublisherScope);

        static::saving(function (Publishable&Model $model) {
            if ($model->isBeingPublished()) {
                if (! Publisher::canPublish($model)) {
                    return false;
                }

                $model->fireBeforePublishing();
                $model->firePublishing();
                $model->fireAfterPublishing();
            } elseif ($model->isBeingUnpublished()) {
                if (! Publisher::canUnpublish($model)) {
                    return false;
                }

                $model->fireBeforeUnpublishing();
                $model->fireUnpublishing();
                $model->fireAfterUnpublishing();
            }

            if ($model->shouldBeDrafted()) {
                $model->fireBeforeDrafting();
                $model->fireDrafting();
                $model->fireAfterDrafting();
            } else {
                $model->fireBeforeUndrafting();
                $model->fireUndrafting();
                $model->fireAfterUndrafting();
            }
        });

        static::saved(function (Publishable&Model $model) {
            if ($model->wasPublished()) {
                $model->fireBeforePublished();
                $model->firePublished();
                $model->fireAfterPublished();
            } elseif ($model->wasUnpublished()) {
                $model->fireBeforeUnpublished();
                $model->fireUnpublished();
                $model->fireAfterUnpublished();
            }

            if ($model->wasDrafted()) {
                $model->fireBeforeDrafted();
                $model->fireDrafted();
                $model->fireAfterDrafted();
            } elseif ($model->wasUndrafted()) {
                $model->fireBeforeUndrafted();
                $model->fireUndrafted();
                $model->fireAfterUndrafted();
            }
        });
    }

    public function workflowColumn(): string
    {
        return config()->get('publisher.columns.workflow', 'status');
    }

    public function hasBeenPublishedColumn(): string
    {
        return config()->get('publisher.columns.has_been_published', 'has_been_published');
    }

    public function publishedState(): string
    {
        return 'published';
    }

    public function unpublishedState(): string
    {
        return 'draft';
    }

    public static function query(): PublisherBuilder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query): PublisherBuilder
    {
        return new PublisherBuilder($query);
    }

    public function shouldBeDrafted(): bool
    {
        return $this->attributes[$this->workflowColumn()] !== $this->publishedState();
    }

    public function isBeingPublished(): bool
    {
        return $this->isDirty($this->workflowColumn())
            && $this->attributes[$this->workflowColumn()] === $this->publishedState();
    }

    public function isBeingUnpublished(): bool
    {
        return $this->isDirty($this->workflowColumn())
            && $this->attributes[$this->workflowColumn()] !== $this->publishedState();
    }

    public function isPublished(): bool
    {
        return $this->attributes[$this->workflowColumn()] === $this->publishedState();
    }

    public function isNotPublished(): bool
    {
        return ! $this->isPublished();
    }

    public function wasPublished(): bool
    {
        return $this->wasPublished;
    }

    public function wasUnpublished(): bool
    {
        return $this->wasUnpublished;
    }

    public function wasDrafted(): bool
    {
        return $this->wasDrafted;
    }

    public function wasUndrafted(): bool
    {
        return $this->wasUndrafted;
    }

    public function hasEverBeenPublished(): bool
    {
        return $this->attributes[$this->hasBeenPublishedColumn()] === true;
    }
}
