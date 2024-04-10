<?php

namespace Plank\Publisher\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Plank\Publisher\Builders\PublisherBuilder;
use Plank\Publisher\Facades\Publisher;

class PublisherScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  PublisherBuilder  $builder
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (Publisher::draftContentRestricted()) {
            $builder->onlyPublished();
        } else {
            $builder->withoutQueuedDeletes();
        }
    }
}
