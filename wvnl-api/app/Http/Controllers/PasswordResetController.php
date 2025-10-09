<?php

namespace App\Http\Controllers;

use App\Http\Requests\{ForgotPasswordRequest, ResetPasswordRequest};
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->validated());

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Als het e-mailadres bekend is, hebben we een herstel-link verstuurd.',
            ]);
        }

        return response()->json([
            'message' => 'We konden geen herstel-link versturen voor dit e-mailadres.',
        ], 422);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Je wachtwoord is succesvol aangepast.',
            ]);
        }

        return response()->json([
            'message' => 'De herstel-link is ongeldig of verlopen.',
        ], 422);
    }
}
