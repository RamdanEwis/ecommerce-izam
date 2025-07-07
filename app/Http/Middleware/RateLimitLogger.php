<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log rate limit hits
        if ($response->getStatusCode() === 429) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'route' => $request->route()->getName() ?? $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Add rate limit headers to response
        if ($response->headers->has('X-RateLimit-Limit')) {
            $response->headers->set('X-API-Version', '1.0');
            $response->headers->set('X-RateLimit-Policy', 'sliding-window');
        }

        return $response;
    }
}
