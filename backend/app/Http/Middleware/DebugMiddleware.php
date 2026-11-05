<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('DEBUG MIDDLEWARE', [
            'uri' => $request->uri(),
            'method' => $request->method(),
            'input' => $request->all(),
            'content' => $request->getContent(),
        ]);

        $response = $next($request);

        Log::info('DEBUG MIDDLEWARE AFTER', [
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
