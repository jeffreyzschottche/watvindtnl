<?php

namespace App\Http\Controllers;

use App\Http\Requests\{RegisterRequest, LoginRequest};
use App\Models\User;
use App\Services\Auth\GoogleIdToken;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $r): JsonResponse
    {
        $data = $r->validated();

        // verplichte transforms/defaults
        unset($data['password_confirmation']);
        $data['password'] = Hash::make($data['password']);
        $data['secret_url_pw'] = (string) Str::uuid();

        // als checkbox uitstaat, kan het veld ontbreken → default leeg object
        if (!isset($data['notification_prefs'])) {
            $data['notification_prefs'] = [];
        }

        $user = User::create($data);

        event(new Registered($user));

        // refresh voor casts
        $user->refresh();

        return response()->json([
            'token' => $user->createToken('mobile', ['*'], now()->addDays(max(1, (int) config('wdnl.token_days', 14))))->plainTextToken,
            'user' => $user,
            'message' => 'We hebben je een e-mail gestuurd met een bevestigingslink. Bevestig je e-mailadres om te kunnen inloggen.',
        ], 201);
    }

    public function login(LoginRequest $r): JsonResponse
    {
        $user = User::where('email', $r->email)->first();

        if (!$user || !Hash::check($r->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Bevestig eerst je e-mailadres via de link in je inbox voordat je inlogt.',
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('mobile', ['*'], now()->addDays(max(1, (int) config('wdnl.token_days', 14))))->plainTextToken,
            'user' => $user,
        ]);
    }

    /** Sign in or sign up with a verified Google account; no password or confirmation mail needed. */
    public function google(\Illuminate\Http\Request $request, GoogleIdToken $google): JsonResponse
    {
        $data = $request->validate(['credential' => ['required', 'string', 'max:4096']]);
        $claims = $google->verify($data['credential']);
        if (! $claims) {
            return response()->json(['message' => 'Inloggen met Google is niet gelukt. Probeer het opnieuw.'], 422);
        }

        $user = User::where('google_id', $claims['sub'])->first()
            ?? User::whereRaw('LOWER(email) = ?', [$claims['email']])->first();
        $created = false;
        if (! $user) {
            $user = User::create([
                'name' => mb_substr($claims['name'] ?: Str::before($claims['email'], '@'), 0, 60),
                'username' => $this->uniqueUsername($claims['name'] ?: Str::before($claims['email'], '@')),
                'email' => $claims['email'],
                // Unknown random password: Google users can set one later via "wachtwoord vergeten".
                'password' => Hash::make(Str::random(64)),
                'language' => 'nl',
                'notification_prefs' => [],
            ]);
            $user->forceFill(['secret_url_pw' => (string) Str::uuid()]);
            $created = true;
        }
        // Google has verified this address, so the account counts as confirmed.
        $user->forceFill(['google_id' => $claims['sub'], 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->refresh();

        return response()->json([
            'token' => $user->createToken('mobile', ['*'], now()->addDays(max(1, (int) config('wdnl.token_days', 14))))->plainTextToken,
            'user' => $user,
            'created' => $created,
        ], $created ? 201 : 200);
    }

    private function uniqueUsername(string $base): string
    {
        $slug = Str::limit(Str::slug($base, ''), 30, '') ?: 'stemmer';
        $candidate = $slug;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $slug.random_int(100, 9999);
        }

        return $candidate;
    }

    public function logout(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Uitgelogd.']);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }
}
