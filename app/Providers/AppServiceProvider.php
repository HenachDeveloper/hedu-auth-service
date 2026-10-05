<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(
            strtolower((string) $r->input('company_code')).'|'.strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('internal', fn (Request $r) => Limit::perMinute(300)->by($r->ip()));

        // Documentacao OpenAPI (Scramble) em /docs/api, controlada por API_DOCS_ENABLED.
        Gate::define('viewApiDocs', fn (?object $user = null) => filter_var(env('API_DOCS_ENABLED', false), FILTER_VALIDATE_BOOL));
    }
}
