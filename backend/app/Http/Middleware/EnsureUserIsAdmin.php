<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
