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
     * Get all published attribute values.
     *
     * @return array<string, mixed>
     */
    public function getPublishedAttributes(): array;

    /**
     * Get a specific published attribute value.
     */
    public function getPublishedAttribute(string $key): mixed;

    /**
     * Set multiple published attribute values.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function setPublishedAttributes(array $attributes): static;

    /**
     * Set a specific published attribute value.
     */
    public function setPublishedAttribute(string $key, mixed $value): static;

    /**
     * Determine if a column should be excluded from draft attributes on the Model
     */
    public function isExcludedFromDraft(string $column): bool;
}
