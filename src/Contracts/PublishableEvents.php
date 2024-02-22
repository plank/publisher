<?php

namespace Plank\Publisher\Contracts;

/**
 * @mixin HasEvents
 */
interface PublishableEvents
{
    /**
     * Register a callback to run before the publishing has been fired
     */
    public static function beforePublishing(callable $callback): void;

    /**
     * Register a callback to run when publishing
     */
    public static function publishing(callable $callback): void;

    /**
     * Register a callback to run after the publishing event has finished
     */
    public static function afterPublishing(callable $callback): void;

    /**
     * Register a callback to run before the published has been fired
     */
    public static function beforeUnpublishing(callable $callback): void;

    /**
     * Register a callback to run when published
     */
    public static function unpublishing(callable $callback): void;

    /**
     * Register a callback to run after the published event has finished
     */
    public static function afterUnpublishing(callable $callback): void;

    /**
     * Register a callback to run before the published has been fired
     */
    public static function beforePublished(callable $callback): void;

    /**
     * Register a callback to run when published
     */
    public static function published(callable $callback): void;

    /**
     * Register a callback to run after the published event has finished
     */
    public static function afterPublished(callable $callback): void;

    /**
     * Register a callback to run before the published has been fired
     */
    public static function beforeUnpublished(callable $callback): void;

    /**
     * Register a callback to run when published
     */
    public static function unpublished(callable $callback): void;

    /**
     * Register a callback to run after the published event has finished
     */
    public static function afterUnpublished(callable $callback): void;

    /**
     * Register a callback to run before the drafting has been fired
     */
    public static function beforeDrafting(callable $callback): void;

    /**
     * Register a callback to run when drafting
     */
    public static function drafting(callable $callback): void;

    /**
     * Register a callback to run after the drafting event has finished
     */
    public static function afterDrafting(callable $callback): void;

    /**
     * Register a callback to run before the undrafting has been fired
     */
    public static function beforeUndrafting(callable $callback): void;

    /**
     * Register a callback to run when undrafting
     */
    public static function undrafting(callable $callback): void;

    /**
     * Register a callback to run after the undrafting event has finished
     */
    public static function afterUndrafting(callable $callback): void;

    /**
     * Register a callback to run before the drafted has been fired
     */
    public static function beforeDrafted(callable $callback): void;

    /**
     * Register a callback to run when drafted
     */
    public static function drafted(callable $callback): void;

    /**
     * Register a callback to run after the drafted event has finished
     */
    public static function afterDrafted(callable $callback): void;

    /**
     * Register a callback to run before the undrafted has been fired
     */
    public static function beforeUndrafted(callable $callback): void;

    /**
     * Register a callback to run when undrafted
     */
    public static function undrafted(callable $callback): void;

    /**
     * Register a callback to run after the undrafted event has finished
     */
    public static function afterUndrafted(callable $callback): void;

    /**
     * Fire the model's beforePublishing event
     */
    public function fireBeforePublishing(): void;

    /**
     * Fire the model's publishing event
     */
    public function firePublishing(): void;

    /**
     * Fire the model's afterPublishing event
     */
    public function fireAfterPublishing(): void;

    /**
     * Fire the model's beforeUnpublishing event
     */
    public function fireBeforeUnpublishing(): void;

    /**
     * Fire the model's unpublishing event
     */
    public function fireUnpublishing(): void;

    /**
     * Fire the model's afterUnpublishing event
     */
    public function fireAfterUnpublishing(): void;

    /**
     * Fire the model's beforePublished event
     */
    public function fireBeforePublished(): void;

    /**
     * Fire the model's published event
     */
    public function firePublished(): void;

    /**
     * Fire the model's afterPublished event
     */
    public function fireAfterPublished(): void;

    /**
     * Fire the model's beforeUnpublished event
     */
    public function fireBeforeUnpublished(): void;

    /**
     * Fire the model's unpublished event
     */
    public function fireUnpublished(): void;

    /**
     * Fire the model's afterUnpublished event
     */
    public function fireAfterUnpublished(): void;

    /**
     * Fire the model's beforeDrafting event
     */
    public function fireBeforeDrafting(): void;

    /**
     * Fire the model's drafting event
     */
    public function fireDrafting(): void;

    /**
     * Fire the model's afterDrafting event
     */
    public function fireAfterDrafting(): void;

    /**
     * Fire the model's beforeUndrafting event
     */
    public function fireBeforeUndrafting(): void;

    /**
     * Fire the model's undrafting event
     */
    public function fireUndrafting(): void;

    /**
     * Fire the model's afterUndrafting event
     */
    public function fireAfterUndrafting(): void;

    /**
     * Fire the model's beforeDrafted event
     */
    public function fireBeforeDrafted(): void;

    /**
     * Fire the model's drafted event
     */
    public function fireDrafted(): void;

    /**
     * Fire the model's afterDrafted event
     */
    public function fireAfterDrafted(): void;

    /**
     * Fire the model's beforeUndrafted event
     */
    public function fireBeforeUndrafted(): void;

    /**
     * Fire the model's undrafted event
     */
    public function fireUndrafted(): void;

    /**
     * Fire the model's afterUndrafted event
     */
    public function fireAfterUndrafted(): void;
}
