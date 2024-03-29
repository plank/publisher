<?php

namespace Plank\Publisher\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface PublisherQueries
{
    /**
     * Scope the query to models in the published state
     */
    public function onlyPublished(): Builder&PublisherQueries;

    /**
     * Scope the query to models that are not in the published state
     */
    public function onlyDraft(): Builder&PublisherQueries;

    /**
     * Scope the query to models that are not queued for deletion
     */
    public function withoutQueuedDeletes(): Builder&PublisherQueries;

    /**
     * Scope the query to models that are queued for deletion
     */
    public function onlyQueuedDeletes(): Builder&PublisherQueries;

    /**
     * Scope the query to models that are either queued for deletion or not
     */
    public function withQueuedDeletes(): Builder&PublisherQueries;
}
