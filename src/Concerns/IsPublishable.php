<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Plank\BeforeAndAfterModelEvents\Concerns\BeforeAndAfterEvents;
use Plank\Publisher\Builders\PublisherBuilder;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Contracts\PublishingStatus;
use Plank\Publisher\Exceptions\RevertException;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Scopes\PublisherScope;

/**
 * @mixin Model
 *
 * @see Publishable
 */
trait IsPublishable
{
    use BeforeAndAfterEvents;
    use FiresPublishingEvents;
    use HasPublishableAttributes;
    use HasPublishableRelationships;
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

        $handleSave = static function (Publishable&Model $model) {
            if ($model->isBeingPublished()) {
                if (! Publisher::canPublish($model)) {
                    return false;
                }

                $model->firePublishing();
            } elseif ($model->isBeingUnpublished()) {
                if (! Publisher::canUnpublish($model)) {
                    return false;
                }

                $model->fireUnpublishing();
            }

            if ($model->shouldBeDrafted()) {
                $model->fireDrafting();
            } else {
                $model->fireUndrafting();
            }
        };

        /**
         * `creating` and `updating` get fired last AFTER the folowing events:
         * - saving
         * - soft deleting
         * - restoring
         */
        static::afterEvent('creating', $handleSave);
        static::afterEvent('updating', $handleSave);

        static::saved(function (Publishable&Model $model) {
            if ($model->wasPublished()) {
                $model->firePublished();
            } elseif ($model->wasUnpublished()) {
                $model->fireUnpublished();
            }

            if ($model->wasDrafted()) {
                $model->fireDrafted();
            } elseif ($model->wasUndrafted()) {
                $model->fireUndrafted();
            }
        });
    }

    public function revert(): void
    {
        if (! $this->hasEverBeenPublished()) {
            throw new RevertException('Publishable content cannot be reverted if it has never been published.');
        }

        DB::transaction(function () {
            $this->fireReverting();

            Publisher::withoutDraftContent(fn () => $this->refresh());

            static::withoutHandlers(['publishing', 'published'], function () {
                $this->revertPublishableRelations();

                $this->{$this->draftColumn()} = null;
                $this->{$this->workflowColumn()} = static::workflow()::published();
                $this->{$this->shouldDeleteColumn()} = false;
                $this->save();
            });

            $this->fireReverted();
        });
    }

    public function draftColumn(): string
    {
        return config()->get('publisher.columns.draft', 'draft');
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

    public function shouldLoadFromDraft(): bool
    {
        return Publisher::draftContentAllowed()
            && $this->publisherColumnsSelected()
            && $this->isNotPublished();
    }

    public function publisherColumnsSelected(): bool
    {
        $rawOriginal = $this->getRawOriginal();

        return isset($rawOriginal[$this->workflowColumn()])
            && isset($rawOriginal[$this->draftColumn()]);
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
