<?php

namespace Plank\Publisher\Pivot;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class PublishableMorphPivot extends MorphPivot
{
    use HasPublishablePivotAttributes;
}
