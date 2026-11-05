<?php

return [
    'url' => env('NTFY_URL', 'http://ntfy:80'),
    'token' => env('NTFY_TOKEN'),
    'topic_prefix' => env('NTFY_TOPIC_PREFIX', 'tasksbase-user-'),
];
