<?php

return [
    'columns' => [
        'draft' => 'draft',
        'workflow' => 'status',
        'has_been_published' => 'has_been_published',
    ],
    'middleware' => [
        'enabled' => true,
        'global' => true,
    ],
    'urls' => [
        'rewrite' => true,
        'previewKey' => 'preview',
    ],
    'admin' => [
        'path' => 'admin',
    ],
];
