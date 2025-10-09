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
        $user->update([
            'password' => Hash::make($r->validated('password')),
        ]);

        return response()->json(['message' => 'Password updated']);
    }
}
