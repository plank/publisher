<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Contracts\PublishablePivot;
use Plank\Publisher\Relations\PublishableBelongsToMany;
use Plank\Publisher\Relations\PublishableMorphToMany;

/**
 * @mixin Model
 * @mixin Publishable
 *
 * @property array<string,int> $publishablePivottedRelations
 *
 * @see Publishable
 */
trait HasPublishableRelationships
{
    public static function bootHasPublishableRelationships()
    {
        static::publishing(function (Publishable&Model $model) {
            $model->deleteQueuedPivots();
            $model->publishAllPivots();
        });
    }

    /**
     * @return Collection<PublishablePivot&Relation>
     */
    public function publishablePivots(): Collection
    {
        $relations = property_exists($this, 'publishablePivottedRelations')
            ? Collection::make($this->publishablePivottedRelations)
            : Collection::make();

        return $relations->map(fn (string $relation) => $this->{$relation}());
    }

    protected function revertPublishableRelations()
    {
        $this->publishablePivots()
            ->each(function (Relation&PublishablePivot $relation) {
                $relation
                    ->newPivotStatement()
                    ->where($this->hasBeenPublishedColumn(), false)
                    ->delete();
            });

        $this->publishablePivots()
            ->each(function (Relation&PublishablePivot $relation) {
                $relation
                    ->newPivotStatement()
                    ->where($this->hasBeenPublishedColumn(), false)
                    ->update([
                        $this->shouldDeleteColumn() => false,
                    ]);
            });
    }

    public function deleteQueuedPivots(): void
    {
        $this->publishablePivots()
            ->each(function (Relation&PublishablePivot $relation) {
                $relation
                    ->newPivotStatement()
                    ->where($this->shouldDeleteColumn(), true)
                    ->delete();
            });
    }

    public function publishAllPivots(): void
    {
        $this->publishablePivots()
            ->each(function (Relation&PublishablePivot $relation) {
                $relation
                    ->newPivotStatement()
                    ->where($this->hasBeenPublishedColumn(), false)
                    ->update([
                        $this->hasBeenPublishedColumn() => true,
                    ]);
            });
    }

    /**
     * Define a many-to-many relationship.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TRelatedModel>  $related
     * @param  string|class-string<\Illuminate\Database\Eloquent\Model>|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @param  string|null  $relation
     * @return \Plank\Publisher\Relations\PublishableBelongsToMany<TRelatedModel, $this>
     */
    public function publishableBelongsToMany(
        $related,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null,
    ) {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related, $instance);
        }

        return $this->newPublishableBelongsToMany(
            $instance->newQuery(),
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relation,
        );
    }

    /**
     * Instantiate a new BelongsToMany relationship.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @param  string|class-string<\Illuminate\Database\Eloquent\Model>  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @return \Plank\Publisher\Relations\PublishableBelongsToMany<TRelatedModel, TDeclaringModel>
     */
    protected function newPublishableBelongsToMany(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
    ) {
        return new PublishableBelongsToMany(
            $query,
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName,
        );
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TRelatedModel>  $related
     * @param  string  $name
     * @param  string|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @param  string|null  $relation
     * @param  bool  $inverse
     * @return \Plank\Publisher\Relations\PublishableMorphToMany<TRelatedModel, $this>
     */
    public function publishableMorphToMany(
        $related,
        $name,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null,
        $inverse = false,
    ) {
        $relation = $relation ?: $this->guessBelongsToManyRelation();

        // First, we will need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we will make the query
        // instances, as well as the relationship instances we need for these.
        $instance = $this->newRelatedInstance($related);

        $foreignPivotKey = $foreignPivotKey ?: $name.'_id';

        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();

        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for this relation. This relation will set
        // appropriate query constraints then entirely manage the hydrations.
        if (! $table) {
            $words = preg_split('/(_)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

            $lastWord = array_pop($words);

            $table = implode('', $words).Str::plural($lastWord);
        }

        return $this->newPublishableMorphToMany(
            $instance->newQuery(),
            $this,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relation,
            $inverse,
        );
    }

    /**
     * Instantiate a new publishable MorphToMany relationship.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TRelatedModel>  $query
     * @param  TDeclaringModel  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $parentKey
     * @param  string  $relatedKey
     * @param  string|null  $relationName
     * @param  bool  $inverse
     * @return \Plank\Publisher\Relations\PublishableMorphToMany<TRelatedModel, TDeclaringModel>
     */
    protected function newPublishableMorphToMany(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false,
    ) {
        return new PublishableMorphToMany(
            $query,
            $parent,
            $name,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName,
            $inverse,
        );
    }
}
