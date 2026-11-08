<?php

namespace App\Http\Controllers;

use App\Services\PoliticalCompassService;
use Illuminate\Http\Request;

class PoliticalCompassController extends Controller
{
    public function __construct(private readonly PoliticalCompassService $service) {}

    public function show(Request $request)
    {
        $user = $request->user();

        $latest = $user->politicalCompasses()->latest('created_at')->first();
        $history = $user->politicalCompasses()->orderByDesc('created_at')->take(12)->get();

        $voteIds = array_map('intval', (array) ($user->voted_issue_ids ?? []));
        $voteCount = count($voteIds);
        $minimumVotes = $this->service->minimumVotes();

        $canGenerate = false; // Historical analyses remain readable; all new reports require a separate purchase.
        $nextAvailableAt = $voteCount >= $minimumVotes
            ? $this->service->nextAvailableAt($user)
            : null;

        return response()->json([
            'latest' => $latest ? $this->service->serializeCompass($latest) : null,
            'history' => $history
                ->map(fn ($item) => $this->service->serializeCompass($item))
                ->values(),
            'can_generate' => $canGenerate,
            'next_available_at' => $nextAvailableAt?->toIso8601String(),
            'minimum_votes' => $minimumVotes,
            'vote_count' => $voteCount,
        ]);
    }

    public function store(Request $request)
    {
        return response()->json(['message' => 'Een nieuw stemrapport is een afzonderlijke aankoop van €1,99. Eerdere rapporten blijven beschikbaar.', 'report_url' => '/profiel/mijn-kompas'], 409);
    }
}
