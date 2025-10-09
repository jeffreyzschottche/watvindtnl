<?php

use App\Http\Controllers\EmailVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::get('/reset-password/{token}', function (Request $request, string $token) {
    $frontendBase = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');

    $query = array_filter([
        'token' => $token,
        'email' => $request->query('email'),
    ]);

    $redirectUrl = $frontendBase . '/reset-password' . ($query ? '?' . http_build_query($query) : '');

    return redirect()->away($redirectUrl);
})->name('password.reset');
