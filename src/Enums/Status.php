<?php

namespace Plank\Publisher\Enums;

use Plank\Publisher\Contracts\PublishingStatus;

enum Status: string implements PublishingStatus 
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public static function published(): self
    {
        return self::PUBLISHED;
    }

    public static function unpublished(): self
    {
        return self::DRAFT;
    }
}
