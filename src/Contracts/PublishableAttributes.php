<?php

namespace Plank\Publisher\Contracts;

interface PublishableAttributes
{
    /**
     * Resolve the attributes from their draft state
     */
    public function syncAttributesFromDraft(): void;

    /**
     * Publish all draft attributes
     */
    public function publishAttributes(): void;

    /**
     * Copy the published attributes to the model's draft
     */
    public function putAttributesInDraft(): void;

    /**
     * Determine if a column should be excluded from draft attributes on the Model
     */
    public function isExcludedFromDraft(string $column): bool;

    /**
     * Determine if there are any dirty draftable attributes
     */
    public function hasDirtyDraftableAttributes(): bool;

    /**
     * Get the attributes which are dirty as drafts
     */
    public function getDirtyDraftableAttributes(): array;
}
