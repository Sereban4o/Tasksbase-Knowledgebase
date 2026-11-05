<?php

return [
    'url' => env('NTFY_URL', 'http://ntfy:80'),
    'token' => env('NTFY_TOKEN'),
    'user' => env('NTFY_USER'),
    'password' => env('NTFY_PASSWORD'),
    'topic_prefix' => env('NTFY_TOPIC_PREFIX', 'tasksbase-user-'),
];
