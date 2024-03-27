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
            'beforePublishing',
            'publishing',
            'afterPublishing',
            'beforeUnpublishing',
            'unpublishing',
            'afterUnpublishing',
            'beforePublished',
            'published',
            'afterPublished',
            'beforeUnpublished',
            'unpublished',
            'afterUnpublished',
            'beforeDrafting',
            'drafting',
            'afterDrafting',
            'beforeUndrafting',
            'undrafting',
            'afterUndrafting',
            'beforeDrafted',
            'drafted',
            'afterDrafted',
            'beforeUndrafted',
            'undrafted',
            'afterUndrafted',
        ]);
    }

    public function fireBeforePublishing(): void
    {
        $this->fireModelEvent('beforePublishing');
    }

    public function firePublishing(): void
    {
        $this->fireModelEvent('publishing');
    }

    public function fireAfterPublishing(): void
    {
        $this->fireModelEvent('afterPublishing');
    }

    public function fireBeforeUnpublishing(): void
    {
        $this->fireModelEvent('beforeUnpublishing');
    }

    public function fireUnpublishing(): void
    {
        $this->fireModelEvent('unpublishing');
    }

    public function fireAfterUnpublishing(): void
    {
        $this->fireModelEvent('afterUnpublishing');
    }

    public function fireBeforePublished(): void
    {
        $this->fireModelEvent('beforePublished');
    }

    public function firePublished(): void
    {
        $this->fireModelEvent('published');
    }

    public function fireAfterPublished(): void
    {
        $this->fireModelEvent('afterPublished');
    }

    public function fireBeforeUnpublished(): void
    {
        $this->fireModelEvent('beforeUnpublished');
    }

    public function fireUnpublished(): void
    {
        $this->fireModelEvent('unpublished');
    }

    public function fireAfterUnpublished(): void
    {
        $this->fireModelEvent('afterUnpublished');
    }

    public function fireBeforeDrafting(): void
    {
        $this->fireModelEvent('beforeDrafting');
    }

    public function fireDrafting(): void
    {
        $this->fireModelEvent('drafting');
    }

    public function fireAfterDrafting(): void
    {
        $this->fireModelEvent('afterDrafting');
    }

    public function fireBeforeUndrafting(): void
    {
        $this->fireModelEvent('beforeUndrafting');
    }

    public function fireUndrafting(): void
    {
        $this->fireModelEvent('undrafting');
    }

    public function fireAfterUndrafting(): void
    {
        $this->fireModelEvent('afterUndrafting');
    }

    public function fireBeforeDrafted(): void
    {
        $this->fireModelEvent('beforeDrafted');
    }

    public function fireDrafted(): void
    {
        $this->fireModelEvent('drafted');
    }

    public function fireAfterDrafted(): void
    {
        $this->fireModelEvent('afterDrafted');
    }

    public function fireBeforeUndrafted(): void
    {
        $this->fireModelEvent('beforeUndrafted');
    }

    public function fireUndrafted(): void
    {
        $this->fireModelEvent('undrafted');
    }

    public function fireAfterUndrafted(): void
    {
        $this->fireModelEvent('afterUndrafted');
    }

    public static function beforePublishing(callable $callback): void
    {
        static::registerModelEvent('beforePublishing', $callback);
    }

    public static function publishing(callable $callback): void
    {
        static::registerModelEvent('publishing', $callback);
    }

    public static function afterPublishing(callable $callback): void
    {
        static::registerModelEvent('afterPublishing', $callback);
    }

    public static function beforeUnpublishing(callable $callback): void
    {
        static::registerModelEvent('beforeUnpublishing', $callback);
    }

    public static function unpublishing(callable $callback): void
    {
        static::registerModelEvent('unpublishing', $callback);
    }

    public static function afterUnpublishing(callable $callback): void
    {
        static::registerModelEvent('afterUnpublishing', $callback);
    }

    public static function beforePublished(callable $callback): void
    {
        static::registerModelEvent('beforePublished', $callback);
    }

    public static function published(callable $callback): void
    {
        static::registerModelEvent('published', $callback);
    }

    public static function afterPublished(callable $callback): void
    {
        static::registerModelEvent('afterPublished', $callback);
    }

    public static function beforeUnpublished(callable $callback): void
    {
        static::registerModelEvent('beforeUnpublished', $callback);
    }

    public static function unpublished(callable $callback): void
    {
        static::registerModelEvent('unpublished', $callback);
    }

    public static function afterUnpublished(callable $callback): void
    {
        static::registerModelEvent('afterUnpublished', $callback);
    }

    public static function beforeDrafting(callable $callback): void
    {
        static::registerModelEvent('beforeDrafting', $callback);
    }

    public static function drafting(callable $callback): void
    {
        static::registerModelEvent('drafting', $callback);
    }

    public static function afterDrafting(callable $callback): void
    {
        static::registerModelEvent('afterDrafting', $callback);
    }

    public static function beforeUndrafting(callable $callback): void
    {
        static::registerModelEvent('beforeUndrafting', $callback);
    }

    public static function undrafting(callable $callback): void
    {
        static::registerModelEvent('undrafting', $callback);
    }

    public static function afterUndrafting(callable $callback): void
    {
        static::registerModelEvent('afterUndrafting', $callback);
    }

    public static function beforeDrafted(callable $callback): void
    {
        static::registerModelEvent('beforeDrafted', $callback);
    }

    public static function drafted(callable $callback): void
    {
        static::registerModelEvent('drafted', $callback);
    }

    public static function afterDrafted(callable $callback): void
    {
        static::registerModelEvent('afterDrafted', $callback);
    }

    public static function beforeUndrafted(callable $callback): void
    {
        static::registerModelEvent('beforeUndrafted', $callback);
    }

    public static function undrafted(callable $callback): void
    {
        static::registerModelEvent('undrafted', $callback);
    }

    public static function afterUndrafted(callable $callback): void
    {
        static::registerModelEvent('afterUndrafted', $callback);
    }
}
