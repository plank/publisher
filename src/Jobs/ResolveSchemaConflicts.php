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
use Plank\Publisher\Enums\ConflictType;
use Plank\Publisher\ValueObjects\Conflict;

/**
 * @property Collection<Conflict> $conflicts
 */
class ResolveSchemaConflicts implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $pk,
        public Collection $conflicts,
    ) {
    }

    public function handle()
    {
        $table = $this->conflicts->first()->table;
        $draft = config()->get('publisher.columns.draft');
        
        $rows = DB::table($table)
            ->whereNotNull($draft)
            ->get([$this->pk, $draft]);

        foreach ($rows as $row) {
            DB::table($table)
                ->where($this->pk, $row->{$this->pk})
                ->update([
                    $draft => $this->resolve(json_decode($row->{$draft})),
                ]);
        }
    }

    protected function resolve(object $row): string
    {
        $resolved = $this->conflicts->reduce(function (object $resolved, Conflict $conflict) {
            return match($conflict->type) {
                ConflictType::Dropped => $this->resolveDropped($resolved, $conflict),
                ConflictType::Renamed => $this->resolveRenamed($resolved, $conflict),
            };
        }, $row);

        return json_encode($resolved);
    }

    protected function resolveDropped(object $resolved, Conflict $conflict): object
    {
        unset($resolved->{$conflict->column});

        return $resolved;
    }

    protected function resolveRenamed(object $resolved, Conflict $conflict): object
    {
        $data = $resolved->{$conflict->column};
        unset($resolved->{$conflict->column});
        
        $renamed = $conflict->params['renamedTo'];
        $resolved->{$renamed} = $data;

        return $resolved;
    }
}
