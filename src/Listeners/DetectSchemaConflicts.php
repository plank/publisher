<?php

namespace Plank\Publisher\Listeners;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Plank\Publisher\Contracts\DetectsConflicts;
use Plank\Publisher\Contracts\ResolvesKeys;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;

class DetectSchemaConflicts
{
    public function __construct(
        protected Container $container,
    ) {}

    public function handle(MigrationEnded $event)
    {
        /** @var ResolvesKeys $resolver */
        $resolver = config()->get('publisher.conflicts.resolver');

        /** @var ResolveSchemaConflicts $job */
        $job = config()->get('publisher.conflicts.job');

        $this->usingConflictSchema(fn () => $event->migration->{$event->method}())
            ->each(function (Collection $conflicts) use ($resolver, $job) {
                if ($conflicts->isEmpty()) {
                    return;
                }

                $pk = $resolver::fromTable($conflicts->first()->table);

                Queue::pushOn(
                    config()->get('publisher.conflicts.queue'),
                    new $job($pk, $conflicts),
                );
            });
    }

    /**
     * @return Collection<Collection<Conflict>>
     */
    protected function usingConflictSchema(Closure $callback): Collection
    {
        $active = $this->container->make('db.schema');

        try {
            /** @var Builder&DetectsConflicts $schema */
            $schema = $this->container->make(DetectsConflicts::class);
            $this->container->instance('db.schema', $schema);
            $schema->getConnection()->pretend(fn () => $callback());
        } finally {
            $this->container->instance('db.schema', $active);
        }

        return $schema->getConflicts();
    }
}
