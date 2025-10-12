<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AltchaChallengeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $challengeUrl = config('services.altcha.challenge_url', 'https://api.altcha.org/v1/challenge');

        try {
            $response = Http::acceptJson()->get($challengeUrl, array_filter([
                'secret' => config('services.altcha.secret'),
            ]));
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'De verificatieservice is tijdelijk niet beschikbaar. Probeer het later opnieuw.',
            ], 503);
        }

        if (!$response->ok()) {
            return response()->json([
                'message' => 'De verificatieservice kon niet worden bereikt. Probeer het later opnieuw.',
            ], 502);
        }

        return response()->json($response->json());
    }
}
