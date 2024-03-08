<?php

return [
    'columns' => [
        'draft' => 'draft',
        'workflow' => 'status',
        'has_been_published' => 'has_been_published',
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
];
