<?php

namespace Plank\Publisher\Contracts;

interface PublishablePivot
{
    public function draftDetach($ids = null, $touch = true);

    public function queueDetachUsingCustomClass($ids);
}
