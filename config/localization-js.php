<?php

return [

    /*
     * Set the names of files you want to add to generated javascript.
     * Otherwise all the files will be included.
     *
     * 'messages' => [
     *     'validation',
     *     'forum/thread',
     * ],
     */
    'messages' => [
        'js',
    ],

    /*
     * The default path to use for the generated javascript.
     */
    'source_path' => base_path('lang'),
    'path' => public_path('messages.js'),
];
