<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect('/verification?status=invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/verification?status=already-verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect('/verification?status=success');
    }
}
