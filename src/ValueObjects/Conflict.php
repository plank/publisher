<?php

namespace Plank\Publisher\ValueObjects;

use Plank\Publisher\Enums\ConflictType;

class Conflict
{
    public function __construct(
        public string $table,
        public string $column,
        public ConflictType $type,
        public array $params = [],
    ) {}
}
