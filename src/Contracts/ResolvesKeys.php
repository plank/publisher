<?php

namespace Plank\Publisher\Contracts;

interface ResolvesKeys
{
    public static function fromTable(string $table): string;
}
