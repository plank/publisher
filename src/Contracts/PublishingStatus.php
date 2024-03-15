<?php

namespace Plank\Publisher\Contracts;

interface PublishingStatus
{
    public static function published(): self;

    public static function unpublished(): self;
}
