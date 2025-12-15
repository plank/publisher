<?php

namespace Plank\Publisher\Concerns;

use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Exceptions\PivotException;
use Plank\Publisher\Facades\Publisher;

/**
 * @mixin \Illuminate\Database\Eloquent\Relations\BelongsToMany
 * @mixin \Illuminate\Database\Eloquent\Relations\MorphToMany
 */
trait HasPublishablePivot
{
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
            return parent::detach($ids, $touch);
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
        if ($this->using &&
            ! empty($ids) &&
            empty($this->pivotWheres) &&
            empty($this->pivotWhereIns) &&
            empty($this->pivotWhereNulls)
        ) {
            $results = $this->queueDetachUsingCustomClass($ids);
        } else {
            $query = $this->newPivotQuery();

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
