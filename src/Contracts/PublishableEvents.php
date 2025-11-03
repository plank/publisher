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
}
