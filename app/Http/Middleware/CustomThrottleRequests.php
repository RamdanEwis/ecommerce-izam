<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class CustomThrottleRequests extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return mixed
     */
    public function handle($request, \Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        // Use named rate limits from config
        if (is_string($maxAttempts) && Config::has("cache.rate_limits.{$maxAttempts}")) {
            $rateLimit = Config::get("cache.rate_limits.{$maxAttempts}");
            [$maxAttempts, $decayMinutes] = explode(',', $rateLimit);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }

    /**
     * Resolve the number of attempts if the user is authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $maxAttempts
     * @return int
     */
    protected function resolveMaxAttempts($request, $maxAttempts)
    {
        // Premium users or admins get higher limits
        if ($request->user() && $request->user()->isAdmin()) {
            return $maxAttempts * 2; // Double the limit for admins
        }

        if ($request->user()) {
            return $maxAttempts + 20; // Additional 20 requests for authenticated users
        }

        return $maxAttempts;
    }
}
