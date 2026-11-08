<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureCmsContentToken
{
    public function handle(Request $request, Closure $next, string $ability = 'read', string $scope = 'content')
    {
        abort_if(strlen($request->getContent()) > 524288, 413, 'Maximaal 512 KiB per contentaanvraag.');
        $plain = $request->bearerToken();
        abort_unless(is_string($plain) && strlen($plain) >= 64 && strlen($plain) <= 128, 401, 'CMS-token vereist.');
        $token = DB::table('wdnl_cms_tokens')->where('token_hash', hash('sha256', $plain))->where('expires_at', '>', now())->first();
        abort_unless($token, 401, 'CMS-token ongeldig of verlopen.');
        abort_unless(in_array('cms:'.$scope.':'.$ability, json_decode($token->abilities, true) ?? [], true), 403, 'CMS-contentrecht ontbreekt.');
        $request->attributes->set('cms_token_id', $token->id);

        return $next($request);
    }
}
