<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Illuminate\Support\Facades\Auth;

class QueryTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token');
        
        if ($token && Auth::guard('sanctum')->onceUsingToken($token)) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        
        return $next($request);
    }
}
