<?php

namespace Plank\Publisher\Contracts;

use Plank\Publisher\Builders\PublisherBuilder;

interface PublisherQueries
{
    /**
     * Scope the query to models in the published state
     */
    public function onlyPublished(): PublisherBuilder;

    /**
     * Scope the query to models that are not in the published state
     */
    public function onlyDraft(): PublisherBuilder;
}
