<?php

namespace Plank\Publisher\Contracts;

/**
 * @mixin HasEvents
 */
interface PublishableEvents
{
    /**
     * Register a callback to run when publishing
     */
    public static function publishing(callable $callback): void;

    /**
     * Register a callback to run when published
     */
    public static function unpublishing(callable $callback): void;

    /**
     * Register a callback to run when published
     */
    public static function published(callable $callback): void;

    /**
     * Register a callback to run when published
     */
    public static function unpublished(callable $callback): void;

    /**
     * Register a callback to run when drafting
     */
    public static function drafting(callable $callback): void;

    /**
     * Register a callback to run when undrafting
     */
    public static function undrafting(callable $callback): void;

    /**
     * Register a callback to run when drafted
     */
    public static function drafted(callable $callback): void;

    /**
     * Register a callback to run when undrafted
     */
    public static function undrafted(callable $callback): void;

    /**
     * Register a callback to run when the model is reverting
     */
    public static function reverting(callable $callback): void;

    /**
     * Register a callback to run when the model is reverted
     */
    public static function reverted(callable $callback): void;

    /**
     * Register a callback to run when the model is being queued for delete
     */
    public static function suspending(callable $callback): void;

    /**
     * Register a callback to run when the model has been queued for delete
     */
    public static function suspended(callable $callback): void;

    /**
     * Register a callback to run when the model is being unqueued for delete
     */
    public static function resuming(callable $callback): void;

    /**
     * Register a callback to run when the model has been unqueued for delete
     */
    public static function resumed(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation syncing while in draft
     */
    public static function pivotDraftSyncing(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation synced while in draft
     */
    public static function pivotDraftSynced(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation attaching while in draft
     */
    public static function pivotDraftAttaching(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation attached while in draft
     */
    public static function pivotDraftAttached(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation detaching while in draft
     */
    public static function pivotDraftDetaching(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation detached while in draft
     */
    public static function pivotDraftDetached(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivot record updating while in draft
     */
    public static function pivotDraftUpdating(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivot record updated while in draft
     */
    public static function pivotDraftUpdated(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation reattaching while in draft
     */
    public static function pivotReattaching(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation reattached while in draft
     */
    public static function pivotReattached(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation discarding
     */
    public static function pivotDiscarding(callable $callback): void;

    /**
     * Register a callback to run when the model has a pivotted relation discarded
     */
    public static function pivotDiscarded(callable $callback): void;

    /**
     * Fire the model's publishing event
     */
    public function firePublishing(): void;

    /**
     * Fire the model's unpublishing event
     */
    public function fireUnpublishing(): void;

    /**
     * Fire the model's published event
     */
    public function firePublished(): void;

    /**
     * Fire the model's unpublished event
     */
    public function fireUnpublished(): void;

    /**
     * Fire the model's drafting event
     */
    public function fireDrafting(): void;

    /**
     * Fire the model's undrafting event
     */
    public function fireUndrafting(): void;

    /**
     * Fire the model's drafted event
     */
    public function fireDrafted(): void;

    /**
     * Fire the model's undrafted event
     */
    public function fireUndrafted(): void;

    /**
     * Fire the model's reverting event
     */
    public function fireReverting(): void;

    /**
     * Fire the model's reverted event
     */
    public function fireReverted(): void;

    /**
     * Fire the model's suspending event
     */
    public function fireSuspending(): void;

    /**
     * Fire the model's suspended event
     */
    public function fireSuspended(): void;

    /**
     * Fire the model's resuming event
     */
    public function fireResuming(): void;

    /**
     * Fire the model's resumed event
     */
    public function fireResumed(): void;
}
