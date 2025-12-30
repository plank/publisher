<?php

namespace Plank\Publisher\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class PublishablePivot extends Pivot
{
    use HasPublishablePivotAttributes;
}
