<?php

namespace App\Http\Controllers;

use App\Http\Requests\{UpdateUserRequest, UpdatePasswordRequest};
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function update(UpdateUserRequest $r, User $user): JsonResponse
    {
        $user->update($r->validated());

        return response()->json($user->fresh());
    }

    public function updatePassword(UpdatePasswordRequest $r, User $user): JsonResponse
    {
        if (!Hash::check($r->validated('current_password'), $user->password)) {
            return response()->json([
                'message' => 'Huidig wachtwoord is onjuist.',
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $r) {
            $user->update(['password' => Hash::make($r->validated('password'))]);
            $user->tokens()->delete();
        });

        return response()->json(['message' => 'Password updated']);
    }
}
