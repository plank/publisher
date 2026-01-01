<?php

namespace Plank\Publisher\Tests\Helpers\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class CustomMorphPivot extends MorphPivot
{
    use HasPublishablePivotAttributes;
}
