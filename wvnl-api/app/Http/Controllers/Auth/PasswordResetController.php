<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Support\FrontendUrl;

class PasswordResetController extends Controller
{
    public function show(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return redirect()->away(FrontendUrl::make('password-reset', ['status' => 'invalid']));
        }

        return redirect()->away(FrontendUrl::make('password-reset', [
            'token' => $token,
            'email' => $email,
        ]));
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        $message = 'Als het e-mailadres bij ons bekend is, sturen we je een link om je wachtwoord opnieuw in te stellen.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->away(FrontendUrl::make('login', ['reset' => 'link-sent']))->with('status', $message);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $message = 'Je wachtwoord is succesvol opnieuw ingesteld. Je kunt nu inloggen.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message]);
            }

            return redirect()->away(FrontendUrl::make('login', ['reset' => 'success']))->with('status', $message);
        }

        $errorMessage = __($status);

        if ($request->wantsJson()) {
            return response()->json(['message' => $errorMessage], 422);
        }

        return redirect()->away(FrontendUrl::make('password-reset', [
            'status' => 'error',
            'message' => $errorMessage,
        ]));
    }
}
