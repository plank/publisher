<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;
use Plank\Publisher\Listeners\DetectSchemaConflicts;
use Plank\Publisher\Schema\ConflictSchema;
use Plank\Publisher\Services\KeyResolver;

return [
    'workflow' => Status::class,
    'columns' => [
        'draft' => 'draft',
        'workflow' => 'status',
        'has_been_published' => 'has_been_published',
        'should_delete' => 'should_delete',
    ],
    'urls' => [
        'rewrite' => true,
        'previewKey' => 'preview',
    ],
    'draft_paths' => [
        'admin*',
        'nova-api*',
        'nova-vendor*',
    ],
    'conflicts' => [
        'schema' => ConflictSchema::class,
        'queue' => 'sync',
        'listener' => DetectSchemaConflicts::class,
        'job' => ResolveSchemaConflicts::class,
        'resolver' => KeyResolver::class,
    ],
];
