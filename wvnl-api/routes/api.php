<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\IssueArgsController;
use App\Http\Controllers\UserIssueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AdminArgumentController;
use App\Http\Controllers\Admin\AdminIssueController;
use App\Http\Controllers\Admin\AdminPoliticalPartyController;

Route::get('/questions', [QuestionController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


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

Route::middleware(['auth:sanctum', 'admin.account'])->prefix('admin')->group(function () {
    Route::get('/issues', [AdminIssueController::class, 'index']);
    Route::get('/reports', [AdminIssueController::class, 'reports']);
    Route::post('/issues', [AdminIssueController::class, 'store']);
    Route::delete('/issues/{issue}', [AdminIssueController::class, 'destroy']);

    Route::post('/issues/{issue}/arguments', [AdminArgumentController::class, 'store']);
    Route::delete('/arguments/{argument}', [AdminArgumentController::class, 'destroy']);

    Route::get('/political-parties', [AdminPoliticalPartyController::class, 'index']);
    Route::post('/political-parties', [AdminPoliticalPartyController::class, 'store']);
    Route::patch('/political-parties/{politicalParty}', [AdminPoliticalPartyController::class, 'update']);
});
