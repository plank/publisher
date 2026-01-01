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
            'suspending',
            'suspended',
            'resuming',
            'resumed',
            'pivotDraftSyncing',
            'pivotDraftSynced',
            'pivotDraftAttaching',
            'pivotDraftAttached',
            'pivotDraftDetaching',
            'pivotDraftDetached',
            'pivotDraftUpdating',
            'pivotDraftUpdated',
            'pivotReattaching',
            'pivotReattached',
            'pivotDiscarding',
            'pivotDiscarded',
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

    public function fireSuspending(): void
    {
        $this->fireModelEvent('suspending');
    }

    public function fireSuspended(): void
    {
        $this->fireModelEvent('suspended');
    }

    public function fireResuming(): void
    {
        $this->fireModelEvent('resuming');
    }

    public function fireResumed(): void
    {
        $this->fireModelEvent('resumed');
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

    public static function suspending(callable $callback): void
    {
        static::registerModelEvent('suspending', $callback);
    }

    public static function suspended(callable $callback): void
    {
        static::registerModelEvent('suspended', $callback);
    }

    public static function resuming(callable $callback): void
    {
        static::registerModelEvent('resuming', $callback);
    }

    public static function resumed(callable $callback): void
    {
        static::registerModelEvent('resumed', $callback);
    }

    public static function pivotDraftSyncing(callable $callback): void
    {
        static::registerModelEvent('pivotDraftSyncing', $callback);
    }

    public static function pivotDraftSynced(callable $callback): void
    {
        static::registerModelEvent('pivotDraftSynced', $callback);
    }

    public static function pivotDraftAttaching(callable $callback): void
    {
        static::registerModelEvent('pivotDraftAttaching', $callback);
    }

    public static function pivotDraftAttached(callable $callback): void
    {
        static::registerModelEvent('pivotDraftAttached', $callback);
    }

    public static function pivotDraftDetaching(callable $callback): void
    {
        static::registerModelEvent('pivotDraftDetaching', $callback);
    }

    public static function pivotDraftDetached(callable $callback): void
    {
        static::registerModelEvent('pivotDraftDetached', $callback);
    }

    public static function pivotDraftUpdating(callable $callback): void
    {
        static::registerModelEvent('pivotDraftUpdating', $callback);
    }

    public static function pivotDraftUpdated(callable $callback): void
    {
        static::registerModelEvent('pivotDraftUpdated', $callback);
    }

    public static function pivotReattaching(callable $callback): void
    {
        static::registerModelEvent('pivotReattaching', $callback);
    }

    public static function pivotReattached(callable $callback): void
    {
        static::registerModelEvent('pivotReattached', $callback);
    }

    public static function pivotDiscarding(callable $callback): void
    {
        static::registerModelEvent('pivotDiscarding', $callback);
    }

    public static function pivotDiscarded(callable $callback): void
    {
        static::registerModelEvent('pivotDiscarded', $callback);
    }
}
