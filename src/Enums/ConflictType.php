<?php

namespace Plank\Publisher\Enums;

enum ConflictType: string
{
    case Dropped = 'dropColumn';
    case Renamed = 'renameColumn';
}
