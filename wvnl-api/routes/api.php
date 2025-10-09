<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IssueArgsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserIssueController;

Route::get('/questions', [QuestionController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/email/resend', [AuthController::class, 'resendVerification']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::get('/issues/pending', [UserIssueController::class, 'index']);
    Route::get('/me/votes', [UserIssueController::class, 'history']);
    Route::post('/issues/{issue}/vote', [UserIssueController::class, 'vote']);
    Route::post('/issues/{issue}/report', [ReportController::class, 'reportIssue']);
    Route::post('/arguments/{argument}/report', [ReportController::class, 'reportArgument']);
});

Route::get('/issues-args', [IssueArgsController::class, 'index']);
Route::get('/issues-args/{issue}', [IssueArgsController::class, 'show']);
