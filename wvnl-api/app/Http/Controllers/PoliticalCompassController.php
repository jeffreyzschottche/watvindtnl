<?php

namespace App\Http\Controllers;

use App\Services\PoliticalCompassService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PoliticalCompassController extends Controller
{
    public function __construct(private readonly PoliticalCompassService $service)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();

        $latest = $user->politicalCompasses()->latest('created_at')->first();
        $history = $user->politicalCompasses()->orderByDesc('created_at')->take(12)->get();

        $voteIds = array_map('intval', (array) ($user->voted_issue_ids ?? []));
        $voteCount = count($voteIds);
        $minimumVotes = $this->service->minimumVotes();

        $canGenerate = $voteCount >= $minimumVotes && $this->service->canGenerate($user);
        $nextAvailableAt = $voteCount >= $minimumVotes
            ? $this->service->nextAvailableAt($user)
            : null;

        return response()->json([
            'latest' => $latest ? $this->service->serializeCompass($latest) : null,
            'history' => $history
                ->map(fn($item) => $this->service->serializeCompass($item))
                ->values(),
            'can_generate' => $canGenerate,
            'next_available_at' => $nextAvailableAt?->toIso8601String(),
            'minimum_votes' => $minimumVotes,
            'vote_count' => $voteCount,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $voteIds = array_map('intval', (array) ($user->voted_issue_ids ?? []));
        $voteCount = count($voteIds);
        $minimumVotes = $this->service->minimumVotes();

        if ($voteCount < $minimumVotes) {
            throw ValidationException::withMessages([
                'votes' => "Je hebt minimaal {$minimumVotes} stem nodig voor een politiek kompas.",
            ]);
        }

        if (!$this->service->canGenerate($user)) {
            $next = $this->service->nextAvailableAt($user);

            return response()->json([
                'message' => 'Je kunt maximaal één keer per maand een politiek kompas genereren.',
                'next_available_at' => $next?->toIso8601String(),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $compass = $this->service->generate($user);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($this->service->serializeCompass($compass));
    }
}
