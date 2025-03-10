<?php

namespace Plank\Publisher\Tests\Helpers\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Plank\Publisher\Concerns\IsPublishable;
use Plank\Publisher\Contracts\Publishable;
use Plank\Publisher\Relations\PublishableBelongsToMany;
use Plank\Publisher\Relations\PublishableMorphToMany;
use Plank\Publisher\Tests\Helpers\Models\Pivot\CustomMorphPivot;
use Plank\Publisher\Tests\Helpers\Models\Pivot\CustomPivot;

class Post extends TestModel implements Publishable
{
    use IsPublishable;

    protected array $publishingDependents = ['sections'];

    /**
     * @var array<string,int>
     */
    protected array $publishablePivottedRelations = [
        'featured',
        'customFeatured',
        'media',
        'customMedia',
    ];

    public function featured(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Post::class, 'post_post', 'post_id', 'featured_id');
    }

    public function customFeatured(): PublishableBelongsToMany
    {
        return $this->publishableBelongsToMany(Post::class, 'post_post', 'post_id', 'featured_id')
            ->using(CustomPivot::class);
    }

    public function media(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Media::class, 'mediable');
    }

    public function customMedia(): PublishableMorphToMany
    {
        return $this->publishableMorphToMany(Media::class, 'mediable')
            ->using(CustomMorphPivot::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
