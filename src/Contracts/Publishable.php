<?php

namespace Plank\Publisher\Contracts;

interface Publishable extends PublishableAttributes, PublishableEvents
{
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
     * Get the value of the published state
     */
    public function publishedState(): string;

    /**
     * Get the value of the unpublished state
     */
    public function unpublishedState(): string;

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
}
