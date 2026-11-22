<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('cms-motion-sync', fn ($r) => Limit::perHour(10)->by((string) $r->attributes->get('cms_token_id')));
        RateLimiter::for('cms-motion-statements', fn ($r) => Limit::perHour(60)->by((string) $r->attributes->get('cms_token_id')));
        RateLimiter::for('cms-compass-proposals', fn ($r) => Limit::perHour(40)->by((string) $r->attributes->get('cms_token_id')));
        $key = static fn ($request) => hash('sha256', mb_strtolower(trim((string) $request->input('email'))).'|'.$request->ip());
        $identity = static fn ($request) => (string) ($request->user('sanctum')?->id ?? hash('sha256', (string) $request->ip()));
        $limit = static fn ($name) => max(1, (int) config('wdnl.limits.'.$name));
        RateLimiter::for('api', fn ($r) => Limit::perMinute($limit('api'))->by($identity($r)));
        RateLimiter::for('login', fn ($r) => [
            Limit::perMinutes(15, $limit('login'))->by('account:'.$key($r)),
            Limit::perMinutes(15, 50)->by('ip:'.hash('sha256', (string) $r->ip())),
        ]);
        foreach (['registration', 'password-reset', 'contact'] as $name) {
            RateLimiter::for($name, fn ($r) => [
                Limit::perHour($limit($name))->by('ip:'.hash('sha256', (string) $r->ip())),
                Limit::perHour($limit($name))->by('account:'.hash('sha256', mb_strtolower(trim((string) $r->input('email'))))),
            ]);
        }
        RateLimiter::for('voting', fn ($r) => Limit::perMinute($limit('voting'))->by($identity($r)));
        RateLimiter::for('account-deletion', fn ($r) => Limit::perMinute(6)->by($identity($r)));
        RateLimiter::for('reports', fn ($r) => Limit::perHour($limit('reports'))->by($identity($r)));
        RateLimiter::for('admin', fn ($r) => Limit::perMinute($limit('admin'))->by($identity($r)));
    }
}
