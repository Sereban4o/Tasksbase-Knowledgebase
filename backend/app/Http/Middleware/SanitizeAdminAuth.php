<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SanitizeAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Auth already handled by StartSanctumSession from query string
        // This middleware only ensures sanitize logic if needed in the future
        
        return $next($request);
    }
}
