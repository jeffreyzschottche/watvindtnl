<?php

namespace App\Http\Controllers;

use App\Http\Requests\{UpdateUserRequest, UpdatePasswordRequest};
use App\Models\Issue;
use App\Models\ReportOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

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

        DB::transaction(function () use ($user, $r) {
            $user->update(['password' => Hash::make($r->validated('password'))]);
            $user->tokens()->delete();
        });

        return response()->json(['message' => 'Password updated']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string', 'max:1024']]);

        DB::transaction(function () use ($request, $data) {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Je huidige wachtwoord is onjuist.']);
            }

            $pending = ReportOrder::where('user_id', $user->id)->where('status', 'pending')
                ->orderBy('id')->lockForUpdate()->get(['stripe_session_id']);
            abort_if($pending->contains(fn (ReportOrder $order) => $order->stripe_session_id !== null), 409,
                'Er staat nog een betaalpagina open. Verwijder je account zodra de betaalsessie is afgerond of verlopen.');

            $this->removeVotes($user);
            $user->tokens()->delete();
            Password::deleteToken($user);
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table'))->where('user_id', $user->id)->delete();
            }
            $user->delete();
        }, 3);

        return response()->json(['message' => 'Je account is verwijderd.']);
    }

    private function removeVotes(User $user): void
    {
        $choices = ['agree', 'disagree', 'neutral'];
        $id = (int) $user->id;
        $ids = Issue::query()->where(function (Builder $query) use ($user, $id, $choices) {
            $query->whereIn('id', $user->voted_issue_ids ?? []);
            foreach ($choices as $choice) {
                $query->orWhereJsonContains('votes->'.$choice, $id)
                    ->orWhereJsonContains('votes->'.$choice, (string) $id);
            }
        })->orderBy('id')->pluck('id');

        foreach ($ids->chunk(200) as $batch) {
            $issues = Issue::whereKey($batch->all())->orderBy('id')->lockForUpdate()->get(['id', 'votes']);
            foreach ($issues as $issue) {
                $votes = $issue->votes ?? [];
                foreach ($choices as $choice) {
                    if (isset($votes[$choice])) {
                        $votes[$choice] = array_values(array_filter((array) $votes[$choice], fn ($voter) => (int) $voter !== $id));
                    }
                }
                if ($votes !== ($issue->votes ?? [])) {
                    $issue->votes = $votes;
                    $issue->save();
                }
            }
        }
    }
}
