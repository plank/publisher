<?php

namespace Plank\Publisher\Listeners;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Plank\LaravelSchemaEvents\Events\TableChanged;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;

class HandleSchemaConflicts
{
    public function handle(TableChanged $event)
    {
        $publisherColumns = $this->publisherColumns();

        if ($event->droppedColumns->contains($publisherColumns['draft'])) {
            return;
        }

        if (! Schema::hasColumn($event->table, $publisherColumns['draft'])) {
            return;
        }

        $renamed = $event->renamedColumns->reject(
            fn (array $rename) => in_array($rename['from'], $publisherColumns) || in_array($rename['to'], $publisherColumns)
        );

        $dropped = $event->droppedColumns->reject(
            fn (string $column) => in_array($column, $publisherColumns)
        );

        if ($renamed->isEmpty() && $dropped->isEmpty()) {
            return;
        }

        /** @var ResolveSchemaConflicts $job */
        $job = config()->get('publisher.conflicts.job');

        Queue::pushOn(
            config()->get('publisher.conflicts.queue'),
            new $job($event->table, $renamed, $dropped),
        );
    }

    protected function publisherColumns(): array
    {
        return config()->get('publisher.columns');
    }
}
