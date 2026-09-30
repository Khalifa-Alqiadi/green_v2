<?php

namespace App\Providers;

use App\Http\Middleware\RequireMcpBearerToken;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\InstallerDatabaseManager;
use App\Services\InstallerEnvironmentManager;
use Froiden\LaravelInstaller\Helpers\DatabaseManager;
use Froiden\LaravelInstaller\Helpers\EnvironmentManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EnvironmentManager::class, InstallerEnvironmentManager::class);
        $this->app->bind(DatabaseManager::class, InstallerDatabaseManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        // Fore HTTPs if enabled to fix any problems with mixed contents
        if (request()->isSecure() || request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        Paginator::useBootstrap();

        RateLimiter::for('mcp', function (Request $request) {
            $user = RequireMcpBearerToken::hasBearer($request)
                ? Auth::guard('sanctum')->user() : null;
            $token = $user && method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;
            $key = $token instanceof PersonalAccessToken
                ? 'token:'.$token->getKey().':user:'.$user->getAuthIdentifier()
                : 'ip:'.$request->ip();

            return Limit::perMinute(config('smartend_mcp.rate_limit', 60))->by($key)
                ->response(fn (Request $request, array $headers) => response()->json(['code' => 'RATE_LIMITED'], 429, $headers));
        });

        RateLimiter::for('ai-text', fn (Request $request) => $this->aiLimit($request, 'text_rate_limit', 20));
        RateLimiter::for('ai-image', fn (Request $request) => $this->aiLimit($request, 'image_rate_limit', 3));
    }

    private function aiLimit(Request $request, string $configKey, int $default): Limit
    {
        return Limit::perMinute(config('smartend_ai.'.$configKey, $default))
            ->by((string) ($request->user()?->id ?? $request->ip()))
            ->response(fn (Request $request, array $headers) => response()->json([
                'message' => __('backend.aiErrorGeneric'),
                'code' => 'ai_rate_limited',
            ], 429, $headers));
    }
}
