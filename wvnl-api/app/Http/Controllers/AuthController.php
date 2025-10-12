<?php

namespace App\Http\Controllers;

use App\Http\Requests\{RegisterRequest, LoginRequest};
use App\Models\User;
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
            'token' => $user->createToken('mobile')->plainTextToken,
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
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth()->user());
    }
}
