<?php

namespace Plank\Publisher\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany  as FrameworkBelongsToMany;
use Plank\Publisher\Concerns\DisablesDraftQueryForPivot;

class BelongsToMany extends FrameworkBelongsToMany
{
    use DisablesDraftQueryForPivot;
}
