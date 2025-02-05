<?php

namespace Plank\Publisher\Services;

use Plank\Publisher\Contracts\ResolvesKeys;

class KeyResolver implements ResolvesKeys
{
    public static function fromTable(string $table): string
    {
        return 'id';
    }
}
