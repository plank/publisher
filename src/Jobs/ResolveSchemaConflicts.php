<?php

namespace Plank\Publisher\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Plank\LaravelModelResolver\Facades\Models;

class ResolveSchemaConflicts implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $table,
        public Collection $renamed,
        public Collection $dropped,
    ) {}

    public function handle()
    {
        $draft = config()->get('publisher.columns.draft');

        $pk = ($class = Models::fromTable($this->table))
            ? (new $class)->getKeyName()
            : 'id';

        $rows = DB::table($this->table)
            ->whereNotNull($draft)
            ->get([$pk, $draft]);

        foreach ($rows as $row) {
            DB::table($this->table)
                ->where($pk, $row->{$pk})
                ->update([
                    $draft => $this->resolve(json_decode($row->{$draft})),
                ]);
        }
    }

    protected function resolve(object $row): string
    {
        $this->renamed->each(fn (array $rename) => $this->resolveRenamed($row, $rename['from'], $rename['to']));
        $this->dropped->each(fn (string $column) => $this->resolveDropped($row, $column));

        return json_encode($row);
    }

    protected function resolveDropped(object $row, string $column): void
    {
        unset($row->{$column});
    }

    protected function resolveRenamed(object $row, string $from, string $to): void
    {
        $data = $row->{$from};
        unset($row->{$from});
        $row->{$to} = $data;
    }
}
