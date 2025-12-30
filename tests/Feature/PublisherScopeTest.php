<?php

use Illuminate\Database\Eloquent\Builder;
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
