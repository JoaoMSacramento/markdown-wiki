<?php

declare(strict_types=1);

return [
    'routes' => [
        [
            'name' => 'page#index',
            'url' => '/',
            'verb' => 'GET',
        ],
        [
            'name' => 'api#getWikiRoot',
            'url' => '/api/wiki-root',
            'verb' => 'GET',
        ],
        [
            'name' => 'api#setWikiRoot',
            'url' => '/api/wiki-root',
            'verb' => 'POST',
        ],
        [
            'name' => 'api#listFiles',
            'url' => '/api/files',
            'verb' => 'GET',
        ],
        [
            'name' => 'api#getFile',
            'url' => '/api/file',
            'verb' => 'GET',
        ],
        [
            'name' => 'api#saveFile',
            'url' => '/api/save-file',
            'verb' => 'POST',
        ],
        [
            'name' => 'api#createFile',
            'url' => '/api/create-file',
            'verb' => 'POST',
        ],
        [
            'name' => 'api#createFolder',
            'url' => '/api/create-folder',
            'verb' => 'POST',
        ],
        [
            'name' => 'api#search',
            'url' => '/api/search',
            'verb' => 'GET',
        ],
    ],
];