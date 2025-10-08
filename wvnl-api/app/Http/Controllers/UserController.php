<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function update(UpdateUserRequest $r, User $user): JsonResponse
    {
        $user->update($r->validated());

        return response()->json($user->fresh());
    }
}
