<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Database\Eloquent\Model;
use Plank\Publisher\Contracts\PublishableEvents;

/**
 * @mixin Model
 * @mixin PublishableEvents
 */
trait FiresPublishingEvents
{
    public function initializeFiresPublishingEvents()
    {
        $this->addObservableEvents([
            'publishing',
            'unpublishing',
            'published',
            'unpublished',
            'drafting',
            'undrafting',
            'drafted',
            'undrafted',
            'reverting',
            'reverted',
            'queuingForDelete',
            'queuedForDelete',
        ]);
    }

    public function firePublishing(): void
    {
        $this->fireModelEvent('publishing');
    }

    public function fireUnpublishing(): void
    {
        $this->fireModelEvent('unpublishing');
    }

    public function firePublished(): void
    {
        $this->fireModelEvent('published');
    }

    public function fireUnpublished(): void
    {
        $this->fireModelEvent('unpublished');
    }

    public function fireDrafting(): void
    {
        $this->fireModelEvent('drafting');
    }

    public function fireUndrafting(): void
    {
        $this->fireModelEvent('undrafting');
    }

    public function fireDrafted(): void
    {
        $this->fireModelEvent('drafted');
    }

    public function fireUndrafted(): void
    {
        $this->fireModelEvent('undrafted');
    }

    public function fireReverting(): void
    {
        $this->fireModelEvent('reverting');
    }

    public function fireReverted(): void
    {
        $this->fireModelEvent('reverted');
    }

    public function fireQueuingForDelete(): void
    {
        $this->fireModelEvent('queuingForDelete');
    }

    public function fireQueuedForDelete(): void
    {
        $this->fireModelEvent('queuedForDelete');
    }

    public static function publishing(callable $callback): void
    {
        static::registerModelEvent('publishing', $callback);
    }

    public static function unpublishing(callable $callback): void
    {
        static::registerModelEvent('unpublishing', $callback);
    }

    public static function published(callable $callback): void
    {
        static::registerModelEvent('published', $callback);
    }

    public static function unpublished(callable $callback): void
    {
        static::registerModelEvent('unpublished', $callback);
    }

    public static function drafting(callable $callback): void
    {
        static::registerModelEvent('drafting', $callback);
    }

    public static function undrafting(callable $callback): void
    {
        static::registerModelEvent('undrafting', $callback);
    }

    public static function drafted(callable $callback): void
    {
        static::registerModelEvent('drafted', $callback);
    }

    public static function undrafted(callable $callback): void
    {
        static::registerModelEvent('undrafted', $callback);
    }

    public static function reverting(callable $callback): void
    {
        static::registerModelEvent('reverting', $callback);
    }

    public static function reverted(callable $callback): void
    {
        static::registerModelEvent('reverted', $callback);
    }

    public static function queuingForDelete(callable $callback): void
    {
        static::registerModelEvent('queuingForDelete', $callback);
    }

    public static function queuedForDelete(callable $callback): void
    {
        static::registerModelEvent('queuedForDelete', $callback);
    }
}
