<?php

use Plank\Publisher\Enums\Status;
use Plank\Publisher\Jobs\ResolveSchemaConflicts;
use Plank\Publisher\Listeners\HandleSchemaConflicts;

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
        'queue' => 'sync',
        'listener' => HandleSchemaConflicts::class,
        'job' => ResolveSchemaConflicts::class,
    ],
    'ignore' => [
        'counts' => true,
    ],
];
