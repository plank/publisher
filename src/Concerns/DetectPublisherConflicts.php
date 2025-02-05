<?php

namespace Plank\Publisher\Concerns;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Plank\Publisher\Enums\ConflictType;
use Plank\Publisher\ValueObjects\Conflict;

/**
 * @mixin \Illuminate\Database\Schema\Builder
 */
trait DetectPublisherConflicts
{
    /**
     * @var Collection<Conflict>
     */
    protected Collection $conflicts;

    public function __construct(Connection $connection)
    {
        $this->conflicts = Collection::make();

        parent::__construct($connection);
    }

    /**
     * @return Collection<Collection<Conflict>>
     */
    public function getConflicts(): Collection
    {
        return $this->conflicts
            ->groupBy('table')
            ->filter(function (Collection $conflicts, string $table) {
                return $this->hasColumns($table, config()->get('publisher.columns'));
            })
            ->values();
    }

    public function table($table, Closure $callback)
    {
        $blueprint = $this->createBlueprint($table, $callback);

        foreach ($blueprint->getCommands() as $command) {
            $this->fromCommand($table, $command);
        }
    }

    public function fromCommand(string $table, Fluent $command): void
    {
        match ($command->name) {
            'dropColumn' => $this->droppedConflicts($table, $command),
            'renameColumn' => $this->renamedConflicts($table, $command),
            default => null // do nothing
        };
    }

    protected function droppedConflicts(string $table, Fluent $command)
    {
        foreach (Arr::wrap($command->columns) as $column) {
            $this->conflicts->push(new Conflict(
                $table,
                $column,
                ConflictType::Dropped,
            ));
        }
    }

    protected function renamedConflicts(string $table, Fluent $command)
    {
        $this->conflicts->push(new Conflict(
            $table,
            $command->from,
            ConflictType::Renamed,
            [ 'renamedTo' => $command->to ]
        ));
    }

    /**
     * Get the tables that belong to the database.
     *
     * @return array
     */
    public function getTables()
    {
        return $this->connection->withoutPretending(fn () => parent::getTables());
    }

    /**
     * Get the views that belong to the database.
     *
     * @return array
     */
    public function getViews()
    {
        return $this->connection->withoutPretending(fn () => parent::getViews());
    }

    /**
     * Get the columns for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumns($table)
    {
        return $this->connection->withoutPretending(fn () => parent::getColumns($table));
    }

    /**
     * Get the indexes for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getIndexes($table)
    {
        return $this->connection->withoutPretending(fn () => parent::getIndexes($table));
    }

    /**
     * Get the foreign keys for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getForeignKeys($table)
    {
        return $this->connection->withoutPretending(fn () => parent::getForeignKeys($table));
    }
}