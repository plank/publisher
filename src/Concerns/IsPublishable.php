<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Builders\PublisherBuilder;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Contracts\PublishingStatus;
use Plank\Publisher\Enums\Status;
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
    use SyncsPublishing;

    public function initializeIsPublishable()
    {
        $this->mergePublishableCasts();

        $this->{$this->workflowColumn()} ??= static::workflow()::unpublished();
        $this->{$this->hasBeenPublishedColumn()} ??= false;
        $this->{$this->shouldDeleteColumn()} ??= false;

        $this->makeHidden([
            $this->draftColumn(),
            $this->hasBeenPublishedColumn(),
            $this->shouldDeleteColumn(),
        ]);
    }

    protected function mergePublishableCasts(): void
    {
        $this->mergeCasts([
            $this->workflowColumn() => config()->get('publisher.workflow'),
            $this->draftColumn() => 'json',
            $this->hasBeenPublishedColumn() => 'boolean',
            $this->shouldDeleteColumn() => 'boolean',
        ]);
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

    public function draftColumn(): string
    {
        return config('publisher.columns.draft', 'draft');
    }

    public function workflowColumn(): string
    {
        return config()->get('publisher.columns.workflow', 'status');
    }

    public function hasBeenPublishedColumn(): string
    {
        return config()->get('publisher.columns.has_been_published', 'has_been_published');
    }

    public function shouldDeleteColumn(): string
    {
        return config()->get('publisher.columns.should_delete', 'should_delete');
    }

    /**
     * @return class-string<PublishingStatus>
     */
    public static function workflow(): string
    {
        return config()->get('publisher.workflow');
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
        return $this->{$this->workflowColumn()} !== static::workflow()::published();
    }

    public function isBeingPublished(): bool
    {
        return $this->isDirty($this->workflowColumn())
            && $this->{$this->workflowColumn()} === static::workflow()::published();
    }

    public function isBeingUnpublished(): bool
    {
        return $this->isDirty($this->workflowColumn())
            && $this->{$this->workflowColumn()} !== static::workflow()::published();
    }

    public function isPublished(): bool
    {
        return $this->{$this->workflowColumn()} === static::workflow()::published();
    }

    public function isNotPublished(): bool
    {
        return ! $this->isPublished();
    }

    public function wasPublished(): bool
    {
        return ($this->wasChanged($this->workflowColumn()) || $this->wasRecentlyCreated)
            && $this->{$this->workflowColumn()} === static::workflow()::published();
    }

    public function wasUnpublished(): bool
    {
        return ($this->wasChanged($this->workflowColumn()) || $this->wasRecentlyCreated)
            && $this->{$this->workflowColumn()} !== static::workflow()::published();
    }

    public function wasDrafted(): bool
    {
        return $this->{$this->workflowColumn()} !== static::workflow()::published();
    }

    public function wasUndrafted(): bool
    {
        return $this->{$this->workflowColumn()} === static::workflow()::published();
    }

    public function hasEverBeenPublished(): bool
    {
        return $this->{$this->hasBeenPublishedColumn()};
    }
}
