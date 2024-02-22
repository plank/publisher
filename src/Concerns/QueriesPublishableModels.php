<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Scopes\PublisherScope;

/**
 * @mixin Builder<Publishable>
 *
 * @property Publishable&Model $model
 */
trait QueriesPublishableModels
{
    public function onlyPublished(): self
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->where($this->model->hasBeenPublishedColumn(), true);
    }

    public function onlyDraft(): self
    {
        return $this->withoutGlobalScope(PublisherScope::class)
            ->whereNot($this->model->workflowColumn(), $this->model->publishedState());
    }

    public function withDraft(): self
    {
        return $this->withoutGlobalScope(PublisherScope::class);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (! $this->shouldUseDraftColumn($column)) {
            return parent::where($column, $operator, $value, $boolean);
        }

        return parent::where(function ($query) use ($column, $operator, $value, $boolean) {
            return $query->where($column, $operator, $value, $boolean)
                ->orWhere($this->model->draftColumn().'->'.$column, $operator, $value, $boolean);
        });
    }

    protected function shouldUseDraftColumn(string $column): bool
    {
        return Publisher::draftContentAllowed()
            && ! $this->model->isExcludedFromDraft($column);
    }
}
