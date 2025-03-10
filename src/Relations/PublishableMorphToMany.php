<?php

namespace Plank\Publisher\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Plank\Publisher\Concerns\HasPublishablePivot;
use Plank\Publisher\Contracts\PublishablePivot;

class PublishableMorphToMany extends MorphToMany implements PublishablePivot
{
    use HasPublishablePivot;
}
