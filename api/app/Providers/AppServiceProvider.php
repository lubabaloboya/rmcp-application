<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Document;
use App\Models\RmcpCase;
use App\Policies\ClientPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\RmcpCasePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
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
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(RmcpCase::class, RmcpCasePolicy::class);

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) env('API_RATE_LIMIT_PER_MINUTE', 60))
                ->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('auth', function (Request $request): Limit {
            $emailOrIp = Str::lower((string) ($request->input('email') ?? $request->ip()));

            return Limit::perMinute((int) env('AUTH_RATE_LIMIT_PER_MINUTE', 5))
                ->by($emailOrIp.'|'.$request->ip());
        });

        RateLimiter::for('bulk-imports', function (Request $request): Limit {
            return Limit::perMinute((int) env('BULK_IMPORT_RATE_LIMIT_PER_MINUTE', 5))
                ->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
