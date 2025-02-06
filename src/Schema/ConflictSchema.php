<?php

namespace Plank\Publisher\Schema;

use Illuminate\Database\Schema\Builder;
use Plank\Publisher\Concerns\DetectPublisherConflicts;
use Plank\Publisher\Contracts\DetectsConflicts;

class ConflictSchema extends Builder implements DetectsConflicts
{
    use DetectPublisherConflicts;
}
