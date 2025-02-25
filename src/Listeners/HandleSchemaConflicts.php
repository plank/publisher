<?php

namespace Plank\Publisher\Listeners;

use Illuminate\Support\Facades\Queue;
use Plank\LaravelSchemaEvents\Events\TableChanged;

class HandleSchemaConflicts
{
    public function handle(TableChanged $event)
    {
        if (empty($event->renamedColumns) && empty($event->droppedColumns)) {
            return;
        }

        /** @var ResolveSchemaConflicts $job */
        $job = config()->get('publisher.conflicts.job');

        Queue::pushOn(
            config()->get('publisher.conflicts.queue'),
            new $job($event->table, $event->renamedColumns, $event->droppedColumns),
        );
    }
}
