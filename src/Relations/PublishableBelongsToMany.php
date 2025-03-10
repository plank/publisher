<?php

namespace Plank\Publisher\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Plank\Publisher\Concerns\HasPublishablePivot;
use Plank\Publisher\Contracts\PublishablePivot;

class PublishableBelongsToMany extends BelongsToMany implements PublishablePivot
{
    use HasPublishablePivot;
}
