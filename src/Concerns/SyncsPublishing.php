<?php

namespace Plank\Publisher\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Plank\LaravelHush\Concerns\HushesHandlers;
use Plank\Publisher\Contracts\Publishable;

/**
 * @mixin FiresPublishingEvents
 * @mixin Publishable
 */
trait SyncsPublishing
{
    use HushesHandlers;

    protected static bool $extractingDependsOnPublishableFK = false;

    public static function bootSyncsPublishing()
    {
        static::drafting(function (Publishable&Model $model) {
            $model->syncPublishingToDependents();
        });

        static::undrafting(function (Publishable&Model $model) {
            $model->syncPublishingToDependents();
        });

        static::deleting(function (Publishable&Model $model) {
            return $model->queueForDelete();
        });
    }

    public function syncPublishingToDependents(): void
    {
        $this->getPublishingDependents()
            ->each(fn (Publishable&Model $model) => $model->syncPublishingFrom($this));
    }

    /**
     * @return Collection<Publishable&Model>
     */
    public function getPublishingDependents(): Collection
    {
        return $this->publishingDependents()
            ->map(fn (string $relation) => $this->nestedPluck($relation))
            ->flatten();
    }

    public function publishingDependents(): Collection
    {
        if (property_exists($this, 'publishingDependents')) {
            return Collection::make($this->publishingDependents);
        }

        return Collection::make();
    }

    public function syncPublishingFrom(Publishable&Model $from): void
    {
        $this->setRelation($this->dependendsOnPublishableRelation(), $from);

        $this->{$this->workflowColumn()} = $from->{$this->workflowColumn()};
        $this->save();

        if ($from->isPublished() && $this->{$this->shouldDeleteColumn()}) {
            $this->withoutHandler('deleting', fn () => $this->delete(), [SyncsPublishing::class]);
        }
    }

    public function queueForDelete(): ?bool
    {
        $parent = $this->dependendsOnPublishable();

        if ($parent === null || $parent->isPublished()) {
            return null;
        }

        $this->{$this->shouldDeleteColumn()} = true;

        $this->saveQuietly();

        return false;
    }

    public function dependendsOnPublishable(): (Publishable&Model)|null
    {
        if ($this->dependendsOnPublishableRelation() === null) {
            return null;
        }

        return $this->{$this->dependendsOnPublishableRelation()};
    }

    public function dependendsOnPublishableRelation(): ?string
    {
        if (property_exists($this, 'dependendsOnPublishable')) {
            return $this->dependendsOnPublishable;
        }

        return null;
    }

    public function dependsOnPublishableForeignKey(): ?string
    {
        if (static::$extractingDependsOnPublishableFK) {
            return null;
        }

        if ($relation = $this->dependendsOnPublishableRelation()) {
            // Newing up an instance of some relations like MorphTo will new
            // up an instance of the model, which causes an infinite loop.
            //
            // To extract the FK, we add this check to prevent the loop.
            try {
                static::$extractingDependsOnPublishableFK = true;
                $relation = $this->{$relation}();
            } finally {
                static::$extractingDependsOnPublishableFK = false;
            }
        }

        if ($relation instanceof BelongsTo) {
            return $relation->getForeignKeyName();
        }

        return null;
    }

    /**
     * @return Collection<Publishable&Model>
     */
    protected function nestedPluck(string $relation): Collection
    {
        $models = [$this];

        $relations = explode('.', $relation);

        while ($part = array_shift($relations)) {
            $results = [];

            foreach ($models as $model) {
                $related = $model->{$part}()->withoutGlobalScopes()->get();

                if ($related instanceof Model) {
                    $results[] = $related;

                    continue;
                }

                if ($related instanceof Arrayable) {
                    foreach ($related as $item) {
                        $results[] = $item;
                    }
                }
            }

            $models = $results;
        }

        return Collection::make($models);
    }
}
