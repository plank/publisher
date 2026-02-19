<?php

namespace Plank\Publisher\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

interface Publishable extends PublishableAttributes, PublishableEvents
{
    /**
     * Drop all draft changes and restore the model as published
     */
    public function revert(): void;

    /**
     * Get the name of the column that stores the draft attributes
     */
    public function draftColumn(): string;

    /**
     * Get the name of the column that stores the workflow state
     */
    public function workflowColumn(): string;

    /**
     * Get the name of the column that stores if the model has ever been published
     */
    public function hasBeenPublishedColumn(): string;

    /**
     * Get the name of the column that stores if the model should be deleted
     */
    public function shouldDeleteColumn(): string;

    /**
     * Get the Workflow State Enum
     *
     * @return class-string<PublishingStatus>
     */
    public static function workflow(): string;

    /**
     * Determine if the model should have its attributes stored in draft
     */
    public function shouldBeDrafted(): bool;

    /**
     * Determine if the model should have its attributes loaded from draft
     */
    public function shouldLoadFromDraft(): bool;

    /**
     * Determine if the model was hydrated with the publisher columns
     */
    public function publisherColumnsSelected(): bool;

    /**
     * Calculate if the model is currently in a the published state
     */
    public function isPublished(): bool;

    /**
     * Determine if the Model is in draft
     */
    public function isNotPublished(): bool;

    /**
     * Determine if the Model is being published
     */
    public function isBeingPublished(): bool;

    /**
     * Determine if the Model is being unpublished
     */
    public function isBeingUnpublished(): bool;

    /**
     * Determine if the Model was recently saved as published
     */
    public function wasPublished(): bool;

    /**
     * Determine if the Model was recently saved as draft
     */
    public function wasUnpublished(): bool;

    /**
     * Determine if the Model has ever been published
     */
    public function hasEverBeenPublished(): bool;

    /**
     * Determine if the Model was recently saved as draft
     */
    public function wasDrafted(): bool;

    /**
     * Determine if the Model was recently saved as draft
     */
    public function wasUndrafted(): bool;

    /**
     * Sync the publishing state to dependents
     */
    public function syncPublishingToDependents(): void;

    /**
     * Revert all publishing dependents that have been published before
     */
    public function revertPublishingDependents(): void;

    /**
     * Handle suspending the model automatically
     */
    public function handleAutomaticSuspension(): ?bool;

    /**
     * Sync the publishing state from another model
     */
    public function syncPublishingFrom(Publishable&Model $from): void;

    /**
     * Get a Collection of of dot-notation relations that should be synced
     * with this Model's publishing/visibility state.
     *
     * @return Collection<string>
     */
    public function publishingDependents(): Collection;

    /**
     * Resolve the publishing dependent models
     *
     * @return Collection<Publishable&Model>
     */
    public function getPublishingDependents(): Collection;

    /**
     * Define a many-to-many relationship.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TRelatedModel>  $related
     * @param  string|class-string<\Illuminate\Database\Eloquent\Model>|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @param  string|null  $relation
     * @return \Plank\Publisher\Relations\PublishableBelongsToMany<TRelatedModel, $this>
     */
    public function publishableBelongsToMany(
        $related,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null,
    );

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TRelatedModel>  $related
     * @param  string  $name
     * @param  string|null  $table
     * @param  string|null  $foreignPivotKey
     * @param  string|null  $relatedPivotKey
     * @param  string|null  $parentKey
     * @param  string|null  $relatedKey
     * @param  string|null  $relation
     * @param  bool  $inverse
     * @return \Plank\Publisher\Relations\PublishableMorphToMany<TRelatedModel, $this>
     */
    public function publishableMorphToMany(
        $related,
        $name,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null,
        $inverse = false,
    );

    /**
     * Resolve the publishable pivotted relations
     *
     * @return Collection<PublishablePivot&Relation>
     */
    public function publishablePivots(): Collection;

    /**
     * Delete any pivots that were queued for detaching
     */
    public function deleteQueuedPivots(): void;

    /**
     * Publish all pivots
     */
    public function publishAllPivots(): void;

    /**
     * Suspend the model by marking it for deletion.
     *
     * When the parent is published, the model will be deleted.
     */
    public function suspend(): void;

    /**
     * Resume the model by clearing the should_delete flag.
     */
    public function resume(): void;

    /**
     * Determine if the model is suspended (queued for deletion).
     */
    public function isSuspended(): bool;

    /**
     * Get the Model that this Model depends on for publishing/visibility
     */
    public function dependsOnPublishable(): Publishable|Model|null;

    /**
     * Get the Model that this Model depends on for publishing/visibility
     */
    public function dependsOnPublishableRelation(): ?string;

    /**
     * Get the foreign key name that this Model depends on for publishing/visibility
     */
    public function dependsOnPublishableForeignKey(): ?string;

    /**
     * Get the foreign key type that this Model depends on for publishing/visibility
     */
    public function dependsOnPublishableForeignKeyMorphType(): ?string;
}
