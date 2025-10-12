<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $recipient = config('services.contact.recipient') ?: config('mail.from.address');

        if (!$recipient) {
            return response()->json([
                'message' => 'Contactformulier is tijdelijk niet beschikbaar.',
            ], 500);
        }

        Mail::to($recipient)->send(new ContactFormMail(
            $data['name'],
            $data['email'],
            $data['message'],
        ));

        return response()->json([
            'sent' => true,
        ]);
    }
}
