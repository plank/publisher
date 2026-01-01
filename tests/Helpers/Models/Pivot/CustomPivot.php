<?php

namespace Plank\Publisher\Tests\Helpers\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Plank\Publisher\Concerns\HasPublishablePivotAttributes;

class CustomPivot extends Pivot
{
    use HasPublishablePivotAttributes;
}
