<?php

namespace Plank\Publisher\Contracts;

interface PublisherQueries
{
    /**
     * Scope the query to models in the published state
     */
    public function onlyPublished(): self;

    /**
     * Scope the query to models that are not in the published state
     */
    public function onlyDraft(): self;

    /**
     * Scope the query to models in the process of being published
     */
    public function withDraft(): self;
}
