<?php

namespace Plank\Publisher\Contracts;

interface PublishablePivot
{
    public function draftDetach($ids = null, $touch = true);

    public function queueDetachUsingCustomClass($ids);

    public function discard($ids = null, $touch = true): bool|int;

    public function reattach($ids = null, $touch = true): bool|int;

    public function publish($ids = null, $touch = true): bool|int;

    public function flush($ids = null, $touch = true): bool|int;

    public function publishPivotAttributes($ids = null, $touch = true): int;

    public function revertPivotAttributes($ids = null, $touch = true): int;
}
