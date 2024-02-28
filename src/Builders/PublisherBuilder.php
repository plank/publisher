<?php

namespace Plank\Publisher\Builders;

use Illuminate\Database\Eloquent\Builder;
use Plank\Publisher\Concerns\QueriesPublishableModels;
use Plank\Publisher\Contracts\PublisherQueries;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModelClass>
 */
class PublisherBuilder extends Builder implements PublisherQueries
{
    use QueriesPublishableModels;
}
