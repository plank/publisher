<?php

namespace Plank\Publisher\Concerns;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Contracts\PublisherQueries;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Scopes\PublisherScope;

/**
 * @mixin Builder<Publishable>
 *
 * @property Publishable&Model $model
 */
trait QueriesPublishableModels
{
    public function onlyPublished(): Builder&PublisherQueries
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->where($this->model->qualifyColumn($this->model->hasBeenPublishedColumn()), true);
    }

    public function onlyDraft(): Builder&PublisherQueries
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->whereNot($this->model->qualifyColumn($this->model->workflowColumn()), $this->model::workflow()::published());
    }

    public function withoutQueuedDeletes(): Builder&PublisherQueries
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->where($this->model->qualifyColumn($this->model->shouldDeleteColumn()), false);
    }

    public function withQueuedDeletes(): Builder&PublisherQueries
    {
        return $this->withoutGlobalScope(PublisherScope::class);
    }

    public function onlyQueuedDeletes(): Builder&PublisherQueries
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->where($this->model->qualifyColumn($this->model->shouldDeleteColumn()), true);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($column instanceof Expression) {
            return parent::where($column, $operator, $value, $boolean);
        }

        if ($column instanceof Closure && is_null($operator)) {
            return parent::where($column, $operator, $value, $boolean);
        }

        if (! $this->shouldUseDraftColumn($column)) {
            return parent::where($column, $operator, $value, $boolean);
        }

        $this->draftAllowedQuery($this->query, $column, $operator, $value, $boolean);

        return $this;
    }

    protected function draftAllowedQuery($query, $column, $operator = null, $value = null, $boolean = 'and')
    {
        return $query->where(fn ($query) => $query->where(fn ($query) => $this->publishedWhere($query, $column, $operator, $value))
            ->orWhere(fn ($query) => $this->unpublishedWhere($query, $column, $operator, $value)), null, null, $boolean
        );
    }

    protected function publishedWhere($query, $column, $operator = null, $value = null)
    {
        return $query->where($this->model->qualifyColumn($this->model->workflowColumn()), $this->model::workflow()::published())
            ->where($column, $operator, $value, 'and');
    }

    protected function unpublishedWhere($query, $column, $operator = null, $value = null)
    {
        $column = $this->modelColumn($column) ?? $column;

        return $query->whereNot($this->model->qualifyColumn($this->model->workflowColumn()), $this->model::workflow()::published())
            ->where($this->model->qualifyColumn($this->model->draftColumn()).'->'.$column, $operator, $value, 'and');
    }

    protected function shouldUseDraftColumn(string $column): bool
    {
        if (! Publisher::draftContentAllowed()) {
            return false;
        }

        $column = $this->modelColumn($column);

        return $column !== null && ! $this->model->isExcludedFromDraft($column);
    }

    /**
     * Get the column without its table qualifier, or null when the column is
     * qualified with another table.
     *
     * The draft column only holds this model's attributes. A column qualified
     * with another table (a pivot table, a joined table) has no value in the
     * draft, so it must not be rewritten into a draft lookup. Unqualified
     * columns are assumed to belong to the model. The qualifier may name the
     * model's table with or without its schema, in any letter case.
     */
    protected function modelColumn(string $column): ?string
    {
        $name = Str::before($column, '->');

        if (! str_contains($name, '.')) {
            return $column;
        }

        $qualifier = Str::beforeLast($name, '.');
        $table = $this->model->getTable();

        if (strcasecmp($qualifier, $table) !== 0 && strcasecmp($qualifier, Str::afterLast($table, '.')) !== 0) {
            return null;
        }

        return Str::after($column, $qualifier.'.');
    }
}
