<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        $key = static fn ($request) => hash('sha256', mb_strtolower(trim((string) $request->input('email'))).'|'.$request->ip());
        $identity = static fn ($request) => (string) ($request->user('sanctum')?->id ?? hash('sha256', (string) $request->ip()));
        $limit = static fn ($name) => max(1, (int) config('wdnl.limits.'.$name));
        \Illuminate\Support\Facades\RateLimiter::for('api', fn ($r) => \Illuminate\Cache\RateLimiting\Limit::perMinute($limit('api'))->by($identity($r)));
        \Illuminate\Support\Facades\RateLimiter::for('login', fn ($r) => [
            \Illuminate\Cache\RateLimiting\Limit::perMinutes(15, $limit('login'))->by('account:'.$key($r)),
            \Illuminate\Cache\RateLimiting\Limit::perMinutes(15, 50)->by('ip:'.hash('sha256', (string) $r->ip())),
        ]);
        foreach (['registration', 'password-reset', 'contact'] as $name) {
            \Illuminate\Support\Facades\RateLimiter::for($name, fn ($r) => [
                \Illuminate\Cache\RateLimiting\Limit::perHour($limit($name))->by('ip:'.hash('sha256', (string) $r->ip())),
                \Illuminate\Cache\RateLimiting\Limit::perHour($limit($name))->by('account:'.hash('sha256', mb_strtolower(trim((string) $r->input('email'))))),
            ]);
        }
        \Illuminate\Support\Facades\RateLimiter::for('voting', fn ($r) => \Illuminate\Cache\RateLimiting\Limit::perMinute($limit('voting'))->by($identity($r)));
        \Illuminate\Support\Facades\RateLimiter::for('reports', fn ($r) => \Illuminate\Cache\RateLimiting\Limit::perHour($limit('reports'))->by($identity($r)));
        \Illuminate\Support\Facades\RateLimiter::for('admin', fn ($r) => \Illuminate\Cache\RateLimiting\Limit::perMinute($limit('admin'))->by($identity($r)));
    }
}
