<?php

namespace Plank\Publisher\Relations;

use Illuminate\Database\Eloquent\Relations\MorphToMany as FrameworkMorphToMany;
use Plank\Publisher\Concerns\DisablesDraftQueryForPivot;

class MorphToMany extends FrameworkMorphToMany
{
    use DisablesDraftQueryForPivot;
}
