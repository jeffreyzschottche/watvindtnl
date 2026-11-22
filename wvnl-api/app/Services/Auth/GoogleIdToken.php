<?php

namespace App\Services\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/** Verifies a "Sign in with Google" ID token with Google before we trust its email. */
class GoogleIdToken
{
    /** @return array{sub: string, email: string, name: ?string}|null */
    public function verify(string $credential): ?array
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            return null;
        }
        try {
            $response = Http::acceptJson()->timeout(8)->connectTimeout(5)
                ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $credential]);
        } catch (ConnectionException) {
            return null;
        }
        $claims = $response->successful() ? $response->json() : null;
        if (! is_array($claims)
            || ($claims['aud'] ?? null) !== $clientId
            || ! in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            || ! in_array($claims['email_verified'] ?? null, [true, 'true'], true)
            || (int) ($claims['exp'] ?? 0) < time()
            || ! is_string($claims['sub'] ?? null) || $claims['sub'] === ''
            || ! filter_var($claims['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return [
            'sub' => $claims['sub'],
            'email' => mb_strtolower($claims['email']),
            'name' => is_string($claims['name'] ?? null) ? $claims['name'] : null,
        ];
    }
}
