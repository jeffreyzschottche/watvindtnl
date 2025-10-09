<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccount
{
    private const ADMIN_EMAIL = 'jeffreyzschot@gmail.com';
    private const ADMIN_USERNAME = 'jeffreyzschot';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->email !== self::ADMIN_EMAIL || $user->username !== self::ADMIN_USERNAME) {
            abort(403, 'Only the designated administrator can access this endpoint.');
        }

        return $next($request);
    }
}
