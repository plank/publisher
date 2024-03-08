<?php

namespace Plank\Publisher\Concerns;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            ->where($this->model->hasBeenPublishedColumn(), true);
    }

    public function onlyDraft(): Builder&PublisherQueries
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->whereNot($this->model->workflowColumn(), $this->model->publishedState());
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
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
        return $query->where(fn ($query) =>
            $query->where(fn ($query) => $this->publishedWhere($query, $column, $operator, $value, $boolean))
                ->orWhere(fn ($query) => $this->unpublishedWhere($query, $column, $operator, $value, $boolean))
        );
    }

    protected function publishedWhere($query, $column, $operator = null, $value = null, $boolean = 'and')
    {
        return $query->where($this->model->workflowColumn(), $this->model->publishedState())
            ->where($column, $operator, $value, $boolean);
    }

    protected function unpublishedWhere($query, $column, $operator = null, $value = null, $boolean = 'and')
    {
        return $query->whereNot($this->model->workflowColumn(), $this->model->publishedState())
            ->where($this->model->draftColumn().'->'.$column, $operator, $value, $boolean);
    }

    protected function shouldUseDraftColumn(string $column): bool
    {
        return Publisher::draftContentAllowed()
            && ! $this->model->isExcludedFromDraft($column);
    }
}
