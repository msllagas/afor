<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\File;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Statuses that get the app's own error page instead of Laravel's.
     */
    private const array ERROR_PAGE_STATUSES = [403, 404, 419, 429, 500, 503];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(!app()->isProduction());
        DB::prohibitDestructiveCommands(app()->isProduction());

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        JsonResource::withoutWrapping();

        // Every upload is an avatar or a logo, so both accept the same images.
        File::defaults(fn () => File::image()->types(['jpeg', 'jpg', 'png', 'gif', 'webp'])->max(2 * 1024));

        // Reads are never throttled; changes are, per user, so scripts can't flood the database.
        RateLimiter::for('writes', fn (Request $request) => $request->isMethodSafe()
            ? Limit::none()
            : Limit::perMinute(config('limits.writes_per_minute'))->by($request->user()?->id ?: $request->ip()));

        Inertia::handleExceptionsUsing(fn (ExceptionResponse $response) => $this->renderErrorPage($response));
    }

    /**
     * Show the branded error page for page visits. JSON callers keep their JSON errors,
     * and server errors keep Laravel's stack trace while debugging.
     */
    private function renderErrorPage(ExceptionResponse $response): ?ExceptionResponse
    {
        $status = $response->statusCode();
        $request = $response->request;

        if (!in_array($status, self::ERROR_PAGE_STATUSES, true)
            || ($request->expectsJson() && !$request->hasHeader('X-Inertia'))
            || ($status >= 500 && config('app.debug'))
        ) {
            return null;
        }

        return $response->render('ErrorPage', [
            'status' => $status,
            // A missing invite means the link was reset, the workspace is gone, or the token never existed.
            'reason' => $status === 404 && $request->routeIs('workspace-invitations.*') ? 'invitation' : null,
        ])->withSharedData();
    }
}
