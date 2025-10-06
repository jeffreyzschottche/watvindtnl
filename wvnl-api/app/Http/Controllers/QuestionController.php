<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function index(): JsonResponse
    {
        // Simpele “alles werkt” response
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'text' => 'Wat is je favoriete kleur?',
                    'choices' => ['rood', 'blauw', 'groen'],
                    'type' => 'multiple_choice',
                ],
            ],
            'meta' => [
                'count' => 1,
                'status' => 'ok',
            ],
        ]);
    }
}
