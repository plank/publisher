<?php

namespace Plank\Publisher\Contracts;

use Illuminate\Support\Collection;
use Plank\Publisher\ValueObjects\Conflict;

interface DetectsConflicts
{
    /**
     * Get a collection of Conflict objects grouped by their table
     *
     * @return Collection<Collection<Conflict>>
     */
    public function getConflicts(): Collection;
}
