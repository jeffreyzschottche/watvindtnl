<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function update(UpdateUserRequest $r, User $user): JsonResponse
    {
        // optioneel: autorisatie – alleen eigenaar mag
        if (auth()->id() !== $user->id) {
            abort(403, 'Forbidden');
        }

        $data = $r->validated();
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }
}
