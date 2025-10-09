<?php

namespace App\Http\Controllers;

use App\Http\Requests\{LoginRequest, RegisterRequest, ResendVerificationEmailRequest};
use App\Models\User;
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

        // verstuur verificatie-mail
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Account aangemaakt. Controleer je e-mail om je registratie te voltooien.',
        ], 201);
    }

    public function login(LoginRequest $r): JsonResponse
    {
        $user = User::where('email', $r->email)->first();

        if (!$user || !Hash::check($r->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'verifieer eerst je emailadress'], 403);
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

    public function resendVerification(ResendVerificationEmailRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if ($user && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Als het e-mailadres bekend is, hebben we een nieuwe verificatiemail gestuurd.',
        ]);
    }
}
