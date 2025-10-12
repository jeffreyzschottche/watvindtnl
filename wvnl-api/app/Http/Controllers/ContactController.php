<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function __invoke(ContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $secret = config('services.altcha.secret');
        if (empty($secret)) {
            abort(500, 'Altcha secret is niet geconfigureerd.');
        }

        $verifyUrl = config('services.altcha.verify_url', 'https://api.altcha.org/v1/verify');

        $verificationPayload = array_filter([
            'token' => data_get($validated, 'altcha.token'),
            'secret' => $secret,
            'challenge' => data_get($validated, 'altcha.challenge'),
            'answer' => data_get($validated, 'altcha.answer'),
            'signature' => data_get($validated, 'altcha.signature'),
        ], fn ($value) => !is_null($value));

        try {
            $response = Http::asJson()->post($verifyUrl, $verificationPayload);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'De verificatieservice is tijdelijk niet beschikbaar. Probeer het later opnieuw.',
            ], 503);
        }

        if (!$response->ok() || !data_get($response->json(), 'success')) {
            return response()->json([
                'message' => 'We konden je inzending niet verifiëren. Probeer het opnieuw.',
            ], 422);
        }

        $recipient = config('mail.contact_recipient') ?? config('mail.from.address');
        if (empty($recipient)) {
            abort(500, 'Er is geen ontvanger ingesteld voor contactformulieren.');
        }

        Mail::to($recipient)->send(new ContactMessageMail(
            $validated['name'],
            $validated['email'],
            $validated['message'],
        ));

        return response()->json([
            'success' => true,
            'message' => 'Bericht verzonden.',
        ]);
    }
}
