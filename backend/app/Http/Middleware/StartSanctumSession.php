<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StartSanctumSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // First, try to authenticate from query string token (for Blade views)
        $queryStringToken = $request->query('token');
        
        if ($queryStringToken) {
            Log::info('Query string token found: ' . substr($queryStringToken, 0, 10) . '...');
            
            if (!Auth::check()) {
                $tokenModel = PersonalAccessToken::findToken($queryStringToken);
                
                if ($tokenModel && $tokenModel->tokenable) {
                    Auth::guard('web')->login($tokenModel->tokenable);
                    Log::info('User authenticated from query string token. User ID: ' . $tokenModel->tokenable->id);
                } else {
                    Log::warning('Token not found or invalid: ' . substr($queryStringToken, 0, 10) . '...');
                }
            }
        }
        
        // Stateless Sanctum authentication via Bearer token
        $token = $request->bearerToken();
        if ($token && !Auth::check()) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                Auth::login($accessToken->tokenable);
            }
        }
        
        if (Auth::check()) {
            Log::info('User is authenticated. User ID: ' . Auth::id());
        } else {
            Log::warning('User is NOT authenticated after middleware');
        }
        
        return $next($request);
    }
}
