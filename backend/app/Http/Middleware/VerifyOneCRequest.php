<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates inbound 1С requests.
 *
 * - Bearer token (or X-Api-Token header) compared in constant time.
 * - Optional HMAC: X-Signature = hex(hmac_sha256("{X-Timestamp}.{rawBody}", secret)).
 * - X-Timestamp must be within the configured tolerance (replay protection).
 */
class VerifyOneCRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('one_c.enabled')) {
            abort(503, '1C integration is disabled');
        }

        $token = (string) config('one_c.token');
        $secret = (string) config('one_c.hmac_secret');

        // Optional IP allowlist.
        $allowedIps = (array) config('one_c.allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps, true)) {
            abort(403, 'Source IP is not allowed for 1C integration');
        }

        // Fail closed: an enabled integration without any credentials must not
        // expose the endpoints unauthenticated.
        if ($token === '' && $secret === '') {
            abort(503, '1C integration is not configured');
        }

        if ($token !== '') {
            $provided = $request->bearerToken() ?: $request->header('X-Api-Token');
            if (!$provided || !hash_equals($token, (string) $provided)) {
                abort(401, 'Invalid 1C token');
            }
        }

        if ($secret !== '') {
            $signature = (string) $request->header('X-Signature');
            $timestamp = (string) $request->header('X-Timestamp');

            if ($signature === '' || $timestamp === '') {
                abort(401, 'Missing 1C signature');
            }

            $tolerance = (int) config('one_c.tolerance', 300);
            if (abs(time() - (int) $timestamp) > $tolerance) {
                abort(401, 'Stale 1C request');
            }

            $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);
            if (!hash_equals($expected, $signature)) {
                abort(401, 'Invalid 1C signature');
            }
        }

        return $next($request);
    }
}
