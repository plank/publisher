<?php

namespace Plank\Publisher\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Plank\Publisher\Contracts\PublisherQueries;
use Plank\Publisher\Facades\Publisher;

class PublisherScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (! $builder instanceof PublisherQueries) {
            return;
        }

        if (Publisher::draftContentRestricted()) {
            $builder->onlyPublished();
        } else {
            $builder->withoutQueuedDeletes();
        }
    }
}
