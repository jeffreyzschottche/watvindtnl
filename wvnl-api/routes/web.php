<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Support\FrontendUrl;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/verification', function () {
    return redirect()->away(FrontendUrl::make('verification'));
})->name('verification.notice');

Route::get('/password-reset', [PasswordResetController::class, 'show'])->name('password.reset');
Route::post('/password-reset', [PasswordResetController::class, 'reset']);
