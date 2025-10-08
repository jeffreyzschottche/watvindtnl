<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\IssueArgsController;
use App\Http\Controllers\UserIssueController;

Route::get('/questions', [QuestionController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::get('/issues/pending', [UserIssueController::class, 'index']);
    Route::post('/issues/{issue}/vote', [UserIssueController::class, 'vote']);
});

Route::get('/issues-args', [IssueArgsController::class, 'index']);
Route::get('/issues-args/{issue}', [IssueArgsController::class, 'show']);