<?php

namespace Plank\Publisher\Contracts;

use Illuminate\Database\Eloquent\Model;
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
     * Queue the model for deletion if it's owner is not published
     */
    public function queueForDelete(): ?bool;

    /**
     * Get the Model that this Model depends on for publishing/visibility
     */
    public function dependendsOnPublishable(): (Publishable&Model)|null;

    /**
     * Get the Model that this Model depends on for publishing/visibility
     */
    public function dependendsOnPublishableRelation(): ?string;

    /**
     * Get the Model the foreign key that this Model depends on for
     * publishing/visibility
     */
    public function dependsOnPublishableForeignKey(): ?string;
}
