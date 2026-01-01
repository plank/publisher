<?php

namespace Plank\Publisher\Tests\Helpers\Support;

use Plank\Publisher\Tests\Helpers\Models\Post;

/**
 * Helper class to track all pivot events on the Post model.
 */
class PivotEventTracker
{
    public array $firedEvents = [];

    public static function make(): self
    {
        return new self();
    }

    public function __construct()
    {
        $allPivotEvents = [
            'pivotAttaching',
            'pivotAttached',
            'pivotDetaching',
            'pivotDetached',
            'pivotUpdating',
            'pivotUpdated',
            'pivotSyncing',
            'pivotSynced',
            'pivotDraftSyncing',
            'pivotDraftSynced',
            'pivotDraftAttaching',
            'pivotDraftAttached',
            'pivotDraftDetaching',
            'pivotDraftDetached',
            'pivotReattaching',
            'pivotReattached',
            'pivotDiscarding',
            'pivotDiscarded',
        ];

        foreach ($allPivotEvents as $event) {
            Post::registerModelEvent($event, function () use ($event) {
                $this->firedEvents[] = $event;
            });
        }
    }

    public function assertOnly(array $expectedEvents): void
    {
        expect($this->firedEvents)->toBe($expectedEvents)
            ->and(count($this->firedEvents))->toBe(count($expectedEvents));
    }
}