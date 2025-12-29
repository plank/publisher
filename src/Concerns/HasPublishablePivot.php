<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Query\Builder as Query;
use Plank\LaravelPivotEvents\Traits\FiresPivotEventsTrait;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Exceptions\PivotException;
use Plank\Publisher\Facades\Publisher;

/**
 * @mixin \Illuminate\Database\Eloquent\Relations\BelongsToMany
 * @mixin \Illuminate\Database\Eloquent\Relations\MorphToMany
 */
trait HasPublishablePivot
{
    use FiresPivotEventsTrait {
        FiresPivotEventsTrait::sync as pivotEventsSync;
        FiresPivotEventsTrait::attach as pivotEventsAttach;
        FiresPivotEventsTrait::detach as pivotEventsDetach;
    }

    public function sync($ids, $detaching = true)
    {
        if ($this->isPublished() || ! $this->hasEverBeenPublished()) {
            return $this->pivotEventsSync($ids, $detaching);
        }

        return $this->draftSync($ids, $detaching);
    }

    protected function draftSync($ids, $detaching = true)
    {
        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids);

        if ($this->parent->firePivotEvent('pivotDraftSyncing', true, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        $parentResult = [];
        $this->parent->withoutEvents(function () use ($ids, $detaching, &$parentResult) {
            $parentResult = parent::sync($ids, $detaching);
        });

        if ($this->parent->firePivotEvent('pivotDraftSynced', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        return $parentResult;
    }

    /**
     * Attach a model to the parent.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool  $touch
     * @return void
     */
    public function attach($ids, array $attributes = [], $touch = true)
    {
        if ($this->isPublished() || ! $this->hasEverBeenPublished()) {
            return $this->pivotEventsAttach($ids, $attributes, $touch);
        }

        return $this->draftAttach($ids, $attributes, $touch);
    }

    protected function draftAttach($ids, array $attributes = [], $touch = true)
    {
        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids, $attributes);

        if ($this->parent->firePivotEvent('pivotDraftAttaching', true, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        $parentResult = parent::attach($ids, $attributes, $touch);

        if ($this->parent->firePivotEvent('pivotDraftAttached', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        return $parentResult;
    }

    /**
     * Detach models from the relationship.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function detach($ids = null, $touch = true)
    {
        if ($this->isPublished() || ! $this->hasEverBeenPublished()) {
            return $this->pivotEventsDetach($ids, $touch);
        }

        return $this->draftDetach($ids, $touch);
    }

    /**
     * Detach models from the relationship while the parent model is not published.
     *
     * @param  mixed  $ids
     * @param  bool  $touch
     * @return int
     */
    public function draftDetach($ids = null, $touch = true)
    {
        if (is_null($ids)) {
            $ids = $this->query->pluck($this->query->qualifyColumn($this->relatedKey))->toArray();
        }

        [$idsOnly] = $this->getIdsWithAttributes($ids);

        if ($this->parent->firePivotEvent('pivotDraftDetaching', true, $this->getRelationName(), $idsOnly) === false) {
            return false;
        }

        if ($this->using &&
            ! empty($ids) &&
            empty($this->pivotWheres) &&
            empty($this->pivotWhereIns) &&
            empty($this->pivotWhereNulls)
        ) {
            $results = $this->queueDetachUsingCustomClass($ids);
        } else {
            $query = parent::newPivotQuery();

            // If associated IDs were passed to the method we will only delete those
            // associations, otherwise all of the association ties will be broken.
            // We'll return the numbers of affected rows when we do the deletes.
            if (! is_null($ids)) {
                $ids = $this->parseIds($ids);

                if (empty($ids)) {
                    return 0;
                }

                $query->whereIn($this->getQualifiedRelatedPivotKeyName(), (array) $ids);
            }

            // Once we have all of the conditions set on the statement, we are ready
            // to run the delete on the pivot table. Then, if the touch parameter
            // is true, we will go ahead and touch all related models to sync.
            $results = $query->update([
                config()->get('publisher.columns.should_delete') => true,
            ]);
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        if ($this->parent->firePivotEvent('pivotDraftDetached', true, $this->getRelationName(), $idsOnly) === false) {
            return false;
        }

        return $results;
    }

    /**
     * Detach models from the relationship using a custom class.
     *
     * @param  mixed  $ids
     * @return int
     */
    public function queueDetachUsingCustomClass($ids)
    {
        $results = 0;

        foreach ($this->parseIds($ids) as $id) {
            $pivot = $this->newPivot([
                $this->foreignPivotKey => $this->parent->{$this->parentKey},
                $this->relatedPivotKey => $id,
            ], true);

            $draftDetachColumn = config()->get('publisher.columns.should_delete');

            $pivot->{$draftDetachColumn} = true;

            if ($pivot->isDirty($draftDetachColumn)) {
                $pivot->save();
                $results += 1;
            }
        }

        return $results;
    }

    public function discard($ids = null, $touch = true): bool|int
    {
        /** @var Query $pivotQuery */
        $pivotQuery = parent::newPivotQuery()
            ->where(config()->get('publisher.columns.has_been_published'), false)
            ->when($ids, fn (Query $query) => $query->whereIn(
                $this->getRelatedPivotKeyName(),
                $ids,
            ));

        $ids = $pivotQuery->get($this->getRelatedPivotKeyName())
                ->pluck($this->getRelatedPivotKeyName())
                ->toArray();

        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids);

        if ($this->parent->firePivotEvent('pivotDiscarding', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        $pivotQuery->delete();

        if ($touch) {
            $this->touchIfTouching();
        }

        if ($this->parent->firePivotEvent('pivotDiscarded', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        return count($ids);
    }

    public function reattach($ids = null, $touch = true): bool|int
    {
        /** @var Query $pivotQuery */
        $pivotQuery = parent::newPivotQuery()
            ->where(config()->get('publisher.columns.has_been_published'), true)
            ->when($ids, fn (Query $query) => $query->whereIn(
                $this->getRelatedPivotKeyName(),
                $ids,
            ));

        $ids = $pivotQuery->get($this->getRelatedPivotKeyName())
            ->pluck($this->getRelatedPivotKeyName())
            ->toArray();

        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids);

        if ($this->parent->firePivotEvent('pivotReattaching', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        $pivotQuery->update([
            config()->get('publisher.columns.should_delete') => false,
        ]);

        if ($this->parent->firePivotEvent('pivotReattached', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return count($ids);
    }

    /**
     * Publish all unpublished pivots by marking them as published.
     * Fires pivotAttaching/pivotAttached events for newly published pivots.
     */
    public function publish($ids = null, $touch = true): bool|int
    {
        /** @var Query $pivotQuery */
        $pivotQuery = parent::newPivotQuery()
            ->where(config()->get('publisher.columns.has_been_published'), false)
            ->when($ids, fn (Query $query) => $query->whereIn(
                $this->getRelatedPivotKeyName(),
                $ids,
            ));

        $ids = $pivotQuery->get($this->getRelatedPivotKeyName())
            ->pluck($this->getRelatedPivotKeyName())
            ->toArray();

        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids);

        if ($this->parent->firePivotEvent('pivotAttaching', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        $pivotQuery->update([
            config()->get('publisher.columns.has_been_published') => true,
        ]);

        if ($this->parent->firePivotEvent('pivotAttached', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return count($ids);
    }

    public function flush($ids = null, $touch = true): bool|int
    {
        $pivotQuery = parent::newPivotQuery()
            ->where(config()->get('publisher.columns.should_delete'), true)
            ->when($ids, fn (Query $query) => $query->whereIn(
                $this->getRelatedPivotKeyName(),
                $ids,
            ));

        $ids = $pivotQuery->get($this->getRelatedPivotKeyName())
            ->pluck($this->getRelatedPivotKeyName())
            ->toArray();

        [$idsOnly, $idsAttributes] = $this->getIdsWithAttributes($ids);

        if ($this->parent->firePivotEvent('pivotDetaching', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        $result = $pivotQuery->delete();

        if ($this->parent->firePivotEvent('pivotDetached', false, $this->getRelationName(), $idsOnly, $idsAttributes) === false) {
            return false;
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        return $result;
    }

    /**
     * Create a new query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotQuery()
    {
        $query = parent::newPivotQuery();

        if (Publisher::draftContentRestricted()) {
            $query->where(config('publisher.columns.has_been_published'), true);
        } else {
            $query->where(config('publisher.columns.should_delete'), false);
        }

        return $query;
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        $allowingDraftContent = Publisher::draftContentAllowed();

        Publisher::withoutDraftContent(function () use ($allowingDraftContent) {
            $this->query->where(
                $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
            );

            if ($allowingDraftContent) {
                $this->query->where(
                    $this->qualifyPivotColumn(config()->get('publisher.columns.should_delete')), false
                );
            } else {
                $this->query->where(
                    $this->qualifyPivotColumn(config()->get('publisher.columns.has_been_published')), true
                );
            }
        });

        return $this;
    }

    /**
     * Create a new pivot attachment record.
     *
     * @param  int  $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        $record = parent::baseAttachRecord($id, $timed);

        $record[config()->get('publisher.columns.has_been_published')] = $this->isPublished();
        $record[config()->get('publisher.columns.should_delete')] = false;

        return $record;
    }

    /**
     * Determine if the parent of the relationship is currently published
     *
     * @throws PivotException
     */
    protected function isPublished(): bool
    {
        return ($parent = $this->getParent()) instanceof Publishable
            ? $parent->isPublished()
            : true;
    }

    /**
     * Determine if the parent of the relationship is currently published
     *
     * @throws PivotException
     */
    protected function hasEverBeenPublished(): bool
    {
        return ($parent = $this->getParent()) instanceof Publishable
            ? $parent->hasEverBeenPublished()
            : true;
    }
}
