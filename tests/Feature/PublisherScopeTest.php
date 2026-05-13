<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Plank\Publisher\Facades\Publisher;
use Plank\Publisher\Scopes\PublisherScope;
use Plank\Publisher\Tests\Helpers\Models\Post;

describe('PublisherScope applies correctly', function () {
    it('does nothing when builder does not implement PublisherQueries', function () {
        // Create a regular Eloquent model that doesn't use the Publisher traits
        $mockModel = new class extends Model
        {
            protected $table = 'posts';
        };

        $builder = $mockModel->newQuery();
        $scope = new PublisherScope;

        // Apply the scope - should early return without error
        $scope->apply($builder, $mockModel);

        // The query should be unmodified (no where clauses added by the scope)
        $sql = $builder->toSql();

        expect($sql)->toBe('select * from "posts"');
    });

    it('applies onlyPublished when draft content is restricted', function () {
        Publisher::restrictDraftContent();

        $query = Post::query();
        $sql = $query->toSql();

        // Should have the has_been_published = true constraint
        expect($sql)->toContain('has_been_published');
    });

    it('applies withoutQueuedDeletes when draft content is allowed', function () {
        Publisher::allowDraftContent();

        $query = Post::query();
        $sql = $query->toSql();

        // Should have the should_delete = false constraint
        expect($sql)->toContain('should_delete');
    });
});

describe('PublisherScope can be disabled', function () {
    it('is enabled by default', function () {
        expect(Publisher::publisherScopeEnabled())->toBeTrue();
        expect(Publisher::publisherScopeDisabled())->toBeFalse();
    });

    it('short-circuits apply when disabled, producing an unconstrained query', function () {
        Publisher::restrictDraftContent();
        Publisher::disablePublisherScope();

        $sql = Post::query()->toSql();

        expect($sql)->not->toContain('has_been_published');
        expect($sql)->not->toContain('should_delete');

        Publisher::enablePublisherScope();
    });

    it('still short-circuits when draft content is allowed', function () {
        Publisher::allowDraftContent();
        Publisher::disablePublisherScope();

        $sql = Post::query()->toSql();

        expect($sql)->not->toContain('should_delete');
        expect($sql)->not->toContain('has_been_published');

        Publisher::enablePublisherScope();
    });

    it('withoutPublisherScope disables for the duration of the closure and restores', function () {
        Publisher::restrictDraftContent();

        expect(Publisher::publisherScopeEnabled())->toBeTrue();

        Publisher::withoutPublisherScope(function () {
            expect(Publisher::publisherScopeDisabled())->toBeTrue();

            $sql = Post::query()->toSql();
            expect($sql)->not->toContain('has_been_published');
        });

        expect(Publisher::publisherScopeEnabled())->toBeTrue();

        // After restoration the scope is re-applied
        $sql = Post::query()->toSql();
        expect($sql)->toContain('has_been_published');
    });

    it('withPublisherScope re-enables for the duration of the closure and restores', function () {
        Publisher::restrictDraftContent();
        Publisher::disablePublisherScope();

        expect(Publisher::publisherScopeDisabled())->toBeTrue();

        Publisher::withPublisherScope(function () {
            expect(Publisher::publisherScopeEnabled())->toBeTrue();

            $sql = Post::query()->toSql();
            expect($sql)->toContain('has_been_published');
        });

        expect(Publisher::publisherScopeDisabled())->toBeTrue();

        Publisher::enablePublisherScope();
    });

    it('restores state when the closure throws', function () {
        Publisher::restrictDraftContent();

        expect(Publisher::publisherScopeEnabled())->toBeTrue();

        try {
            Publisher::withoutPublisherScope(function () {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
            // swallow
        }

        expect(Publisher::publisherScopeEnabled())->toBeTrue();
    });

    it('restores nested state correctly', function () {
        Publisher::restrictDraftContent();

        expect(Publisher::publisherScopeEnabled())->toBeTrue();

        Publisher::withoutPublisherScope(function () {
            expect(Publisher::publisherScopeDisabled())->toBeTrue();

            Publisher::withPublisherScope(function () {
                expect(Publisher::publisherScopeEnabled())->toBeTrue();
            });

            expect(Publisher::publisherScopeDisabled())->toBeTrue();
        });

        expect(Publisher::publisherScopeEnabled())->toBeTrue();
    });
});
