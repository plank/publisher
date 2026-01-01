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

    /**
     * Get the pivot columns for the relation including the draft column.
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $columns = parent::aliasedPivotColumns();

        $draftColumn = $this->pivotDraftColumn();
        $qualifiedDraft = $this->qualifyPivotColumn($draftColumn).' as pivot_'.$draftColumn;

        if (! in_array($qualifiedDraft, $columns)) {
            $columns[] = $qualifiedDraft;
        }

        return $columns;
    }

    /**
     * Create a new existing pivot model instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newExistingPivot(array $attributes = [])
    {
        // For standard pivots without custom class, merge draft in attributes
        if (! $this->using && Publisher::draftContentAllowed()) {
            $attributes = $this->mergePivotDraftAttributes($attributes);
        }

        $pivot = parent::newExistingPivot($attributes);

        // For custom pivot models with HasPublishablePivotAttributes, sync draft
        // We do this after construction because the model's JSON cast handles decoding
        if ($this->using &&
            Publisher::draftContentAllowed() &&
            method_exists($pivot, 'syncPivotAttributesFromDraft')) {
            $pivot->syncPivotAttributesFromDraft();
        }

        return $pivot;
    }

    /**
     * Merge draft values into pivot attributes.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function mergePivotDraftAttributes(array $attributes): array
    {
        $draftColumn = $this->pivotDraftColumn();

        if (empty($attributes[$draftColumn])) {
            return $attributes;
        }

        $draft = is_string($attributes[$draftColumn])
            ? json_decode($attributes[$draftColumn], true)
            : $attributes[$draftColumn];

        if (is_array($draft)) {
            // Merge draft values into attributes and decode the draft column
            $attributes = array_merge($attributes, $draft);
            $attributes[$draftColumn] = $draft;
        }

        return $attributes;
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

    /**
     * Update an existing pivot record on the table.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool  $touch
     * @return int
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        if ($this->isPublished() || ! $this->hasEverBeenPublished()) {
            return parent::updateExistingPivot($id, $attributes, $touch);
        }

        return $this->draftUpdateExistingPivot($id, $attributes, $touch);
    }

    /**
     * Update an existing pivot record with draft attributes.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @param  bool  $touch
     * @return int
     */
    protected function draftUpdateExistingPivot($id, array $attributes, $touch = true)
    {
        [$idsOnly] = $this->getIdsWithAttributes([$id]);

        if ($this->parent->firePivotEvent('pivotDraftUpdating', true, $this->getRelationName(), $idsOnly, $attributes) === false) {
            return false;
        }

        $pivotDraftColumn = $this->pivotDraftColumn();

        if ($this->using) {
            $pivot = $this->getCurrentPivotForId($id);

            if ($pivot) {
                $draft = $pivot->{$pivotDraftColumn} ?? [];
                $draft = array_merge($draft, $this->filterDraftableAttributes($attributes));
                $pivot->{$pivotDraftColumn} = $draft;
                $pivot->save();
                $updated = 1;
            } else {
                $updated = 0;
            }
        } else {
            $updated = $this->newPivotStatementForId($id)->update([
                $pivotDraftColumn => json_encode(
                    array_merge(
                        $this->getCurrentDraftForId($id),
                        $this->filterDraftableAttributes($attributes)
                    )
                ),
            ]);
        }

        if ($touch) {
            $this->touchIfTouching();
        }

        if ($this->parent->firePivotEvent('pivotDraftUpdated', false, $this->getRelationName(), $idsOnly, $attributes) === false) {
            return false;
        }

        return $updated;
    }

    /**
     * Get the current pivot record for an ID.
     *
     * @param  mixed  $id
     * @return \Illuminate\Database\Eloquent\Relations\Pivot|null
     */
    protected function getCurrentPivotForId($id)
    {
        $pivotData = parent::newPivotQuery()
            ->where($this->relatedPivotKey, $id)
            ->first();

        if (! $pivotData) {
            return null;
        }

        return $this->newPivot((array) $pivotData, true);
    }

    /**
     * Get the current draft data for an ID.
     *
     * @param  mixed  $id
     * @return array
     */
    protected function getCurrentDraftForId($id): array
    {
        $pivotDraftColumn = $this->pivotDraftColumn();

        $result = parent::newPivotQuery()
            ->where($this->relatedPivotKey, $id)
            ->first([$pivotDraftColumn]);

        if (! $result || ! $result->{$pivotDraftColumn}) {
            return [];
        }

        return is_string($result->{$pivotDraftColumn})
            ? json_decode($result->{$pivotDraftColumn}, true)
            : (array) $result->{$pivotDraftColumn};
    }

    /**
     * Filter attributes to only include those that can be stored in draft.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function filterDraftableAttributes(array $attributes): array
    {
        $excluded = [
            'id',
            $this->foreignPivotKey,
            $this->relatedPivotKey,
            $this->pivotDraftColumn(),
            config()->get('publisher.columns.has_been_published'),
            config()->get('publisher.columns.should_delete'),
            $this->createdAt(),
            $this->updatedAt(),
        ];

        return array_filter($attributes, fn ($key) => ! in_array($key, $excluded), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Publish all draft pivot attributes by moving them to real columns.
     */
    public function publishPivotAttributes($ids = null, $touch = true): int
    {
        $pivotDraftColumn = $this->pivotDraftColumn();

        /** @var Query $query */
        $query = parent::newPivotQuery()
            ->whereNotNull($pivotDraftColumn)
            ->when($ids, fn (Query $query) => $query->whereIn(
                $this->getRelatedPivotKeyName(),
                $ids,
            ));

        $pivots = $query->get();
        $updated = 0;

        foreach ($pivots as $pivot) {
            $draft = is_string($pivot->{$pivotDraftColumn})
                ? json_decode($pivot->{$pivotDraftColumn}, true)
                : (array) $pivot->{$pivotDraftColumn};

            if (empty($draft)) {
                continue;
            }

            $updateData = array_merge($draft, [$pivotDraftColumn => null]);

            parent::newPivotQuery()
                ->where($this->relatedPivotKey, $pivot->{$this->relatedPivotKey})
                ->update($updateData);

            $updated++;
        }

        if ($touch && $updated > 0) {
            $this->touchIfTouching();
        }

        return $updated;
    }

    /**
     * Revert all draft pivot attributes by clearing the draft column.
     */
    public function revertPivotAttributes($ids = null, $touch = true): int
    {
        $pivotDraftColumn = $this->pivotDraftColumn();

        /** @var Query $query */
        $query = parent::newPivotQuery()
            ->whereNotNull($pivotDraftColumn)
            ->when($ids, fn (Query $query) => $query->whereIn(
                $this->getRelatedPivotKeyName(),
                $ids,
            ));

        $updated = $query->update([$pivotDraftColumn => null]);

        if ($touch && $updated > 0) {
            $this->touchIfTouching();
        }

        return $updated;
    }

    /**
     * Get the name of the pivot draft column.
     */
    protected function pivotDraftColumn(): string
    {
        return config()->get('publisher.columns.draft', 'draft');
    }

    /**
     * Determine if a column should use the pivot draft column for queries.
     */
    protected function shouldUsePivotDraftColumn(string $column): bool
    {
        $excluded = [
            'id',
            $this->foreignPivotKey,
            $this->relatedPivotKey,
            $this->pivotDraftColumn(),
            config()->get('publisher.columns.has_been_published'),
            config()->get('publisher.columns.should_delete'),
            $this->createdAt(),
            $this->updatedAt(),
        ];

        return Publisher::draftContentAllowed() && ! in_array($column, $excluded);
    }

    /**
     * Add a "where pivot" clause to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (! is_string($column) || ! $this->shouldUsePivotDraftColumn($column)) {
            // Wrap in withoutDraftContent to prevent QueriesPublishableModels from adding
            // draft-aware logic to pivot queries, which would cause ambiguous column references.
            return Publisher::withoutDraftContent(
                fn () => parent::wherePivot($column, $operator, $value, $boolean)
            );
        }

        return $this->wherePivotWithDraft($column, $operator, $value, $boolean);
    }

    /**
     * Add a "where pivot" clause that queries both real and draft columns.
     *
     * When draft content is allowed, we query the "effective" value:
     * - If the draft column has a value for the field, use it
     * - Otherwise, fall back to the real column
     */
    protected function wherePivotWithDraft(string $column, $operator = null, $value = null, $boolean = 'and')
    {
        [$value, $operator] = $this->query->getQuery()->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $pivotTable = $this->getTable();
        $draftColumn = $this->pivotDraftColumn();

        // Wrap in withoutDraftContent to prevent QueriesPublishableModels from adding
        // draft-aware logic to these internal pivot queries, which would cause
        // ambiguous column references when both parent and pivot have a 'draft' column.
        return Publisher::withoutDraftContent(fn () => $this->where(function ($query) use ($pivotTable, $column, $operator, $value, $draftColumn) {
            // Draft column has the value and it matches
            $query->where(function ($q) use ($pivotTable, $column, $operator, $value, $draftColumn) {
                $q->whereNotNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->where("{$pivotTable}.{$draftColumn}->{$column}", $operator, $value);
            })
            // OR draft column doesn't have the value and real column matches
            ->orWhere(function ($q) use ($pivotTable, $column, $operator, $value, $draftColumn) {
                $q->whereNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->where("{$pivotTable}.{$column}", $operator, $value);
            });
        }, null, null, $boolean));
    }

    /**
     * Add a "where pivot in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function wherePivotIn($column, $values, $boolean = 'and', $not = false)
    {
        if (! $this->shouldUsePivotDraftColumn($column)) {
            return Publisher::withoutDraftContent(
                fn () => parent::wherePivotIn($column, $values, $boolean, $not)
            );
        }

        $pivotTable = $this->getTable();
        $draftColumn = $this->pivotDraftColumn();

        $method = $not ? 'whereNotIn' : 'whereIn';

        // Wrap in withoutDraftContent to prevent QueriesPublishableModels from adding
        // draft-aware logic to these internal pivot queries.
        return Publisher::withoutDraftContent(fn () => $this->where(function ($query) use ($pivotTable, $column, $values, $draftColumn, $method) {
            // Draft column has the value and it matches
            $query->where(function ($q) use ($pivotTable, $column, $values, $draftColumn, $method) {
                $q->whereNotNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->{$method}("{$pivotTable}.{$draftColumn}->{$column}", $values);
            })
            // OR draft column doesn't have the value and real column matches
            ->orWhere(function ($q) use ($pivotTable, $column, $values, $draftColumn, $method) {
                $q->whereNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->{$method}("{$pivotTable}.{$column}", $values);
            });
        }, null, null, $boolean));
    }

    /**
     * Add a "where pivot not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @param  string  $boolean
     * @return $this
     */
    public function wherePivotNotIn($column, $values, $boolean = 'and')
    {
        return $this->wherePivotIn($column, $values, $boolean, true);
    }

    /**
     * Add a "where pivot null" clause to the query.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function wherePivotNull($column, $boolean = 'and', $not = false)
    {
        if (! $this->shouldUsePivotDraftColumn($column)) {
            return Publisher::withoutDraftContent(
                fn () => parent::wherePivotNull($column, $boolean, $not)
            );
        }

        $pivotTable = $this->getTable();
        $draftColumn = $this->pivotDraftColumn();

        $method = $not ? 'whereNotNull' : 'whereNull';

        // Wrap in withoutDraftContent to prevent QueriesPublishableModels from adding
        // draft-aware logic to these internal pivot queries.
        return Publisher::withoutDraftContent(fn () => $this->where(function ($query) use ($pivotTable, $column, $draftColumn, $method) {
            // Draft column has the field - check if it's null/not null
            $query->where(function ($q) use ($pivotTable, $column, $draftColumn, $method) {
                $q->whereNotNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->{$method}("{$pivotTable}.{$draftColumn}->{$column}");
            })
            // OR draft column doesn't have the field - check real column
            ->orWhere(function ($q) use ($pivotTable, $column, $draftColumn, $method) {
                $q->whereNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->{$method}("{$pivotTable}.{$column}");
            });
        }, null, null, $boolean));
    }

    /**
     * Add a "where pivot not null" clause to the query.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @return $this
     */
    public function wherePivotNotNull($column, $boolean = 'and')
    {
        return $this->wherePivotNull($column, $boolean, true);
    }

    /**
     * Add a "where pivot between" clause to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return $this
     */
    public function wherePivotBetween($column, array $values, $boolean = 'and', $not = false)
    {
        if (! $this->shouldUsePivotDraftColumn($column)) {
            return Publisher::withoutDraftContent(
                fn () => parent::wherePivotBetween($column, $values, $boolean, $not)
            );
        }

        $pivotTable = $this->getTable();
        $draftColumn = $this->pivotDraftColumn();

        $method = $not ? 'whereNotBetween' : 'whereBetween';

        // Wrap in withoutDraftContent to prevent QueriesPublishableModels from adding
        // draft-aware logic to these internal pivot queries.
        return Publisher::withoutDraftContent(fn () => $this->where(function ($query) use ($pivotTable, $column, $values, $draftColumn, $method) {
            // Draft column has the value and it matches
            $query->where(function ($q) use ($pivotTable, $column, $values, $draftColumn, $method) {
                $q->whereNotNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->{$method}("{$pivotTable}.{$draftColumn}->{$column}", $values);
            })
            // OR draft column doesn't have the value and real column matches
            ->orWhere(function ($q) use ($pivotTable, $column, $values, $draftColumn, $method) {
                $q->whereNull("{$pivotTable}.{$draftColumn}->{$column}")
                    ->{$method}("{$pivotTable}.{$column}", $values);
            });
        }, null, null, $boolean));
    }

    /**
     * Add a "where pivot not between" clause to the query.
     *
     * @param  string  $column
     * @param  array  $values
     * @param  string  $boolean
     * @return $this
     */
    public function wherePivotNotBetween($column, array $values, $boolean = 'and')
    {
        return $this->wherePivotBetween($column, $values, $boolean, true);
    }

    /**
     * Add an "or where pivot" clause to the query.
     *
     * @param  \Illuminate\Contracts\Database\Query\Expression|string  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWherePivot($column, $operator = null, $value = null)
    {
        return $this->wherePivot($column, $operator, $value, 'or');
    }

    /**
     * Add an "or where pivot in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return $this
     */
    public function orWherePivotIn($column, $values)
    {
        return $this->wherePivotIn($column, $values, 'or');
    }

    /**
     * Add an "or where pivot not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed  $values
     * @return $this
     */
    public function orWherePivotNotIn($column, $values)
    {
        return $this->wherePivotNotIn($column, $values, 'or');
    }
}
