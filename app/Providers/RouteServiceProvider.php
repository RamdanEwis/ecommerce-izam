<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // Public API Routes (no authentication required)
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/public.php'));

            // Authenticated User API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Admin API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/admin.php'));

            // Authentication Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/auth.php'));

            // Web Routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Public browsing - Higher limit for product browsing
        RateLimiter::for('public_browsing', function (Request $request) {
            return Limit::perMinute(200)->by($request->ip());
        });

        // Search operations - Moderate limit
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        // Authenticated users - Standard limit with user identification
        RateLimiter::for('authenticated', function (Request $request) {
            $baseLimit = 60;
            $userBonus = $request->user() ? 20 : 0;
            $adminBonus = ($request->user() && $request->user()->isAdmin()) ? 40 : 0;

            return Limit::perMinute($baseLimit + $userBonus + $adminBonus)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Write operations - Lower limit for data modification
        RateLimiter::for('write_operations', function (Request $request) {
            $baseLimit = 30;
            $adminBonus = ($request->user() && $request->user()->isAdmin()) ? 20 : 0;

            return Limit::perMinute($baseLimit + $adminBonus)
                ->by($request->user()?->id ?: $request->ip());
        });

        // Admin read operations
        RateLimiter::for('admin_read', function (Request $request) {
            return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
        });

        // Admin write operations
        RateLimiter::for('admin_write', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Bulk operations - Very strict
        RateLimiter::for('bulk_operations', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Heavy operations (reports, exports) - Extra strict
        RateLimiter::for('heavy_operations', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
