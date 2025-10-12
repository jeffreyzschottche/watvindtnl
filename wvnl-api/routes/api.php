<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\IssueArgsController;
use App\Http\Controllers\UserIssueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AdminArgumentController;
use App\Http\Controllers\Admin\AdminIssueController;
use App\Http\Controllers\Admin\AdminPoliticalPartyController;
use App\Http\Controllers\ContactController;

Route::get('/questions', [QuestionController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);
Route::post('/contact', ContactController::class);


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
    Route::patch('/issues/{issue}', [AdminIssueController::class, 'update']);
    Route::post('/issues/import', [AdminIssueController::class, 'bulkStore']);
    Route::delete('/issues/{issue}', [AdminIssueController::class, 'destroy']);

    Route::post('/issues/{issue}/arguments', [AdminArgumentController::class, 'store']);
    Route::patch('/arguments/{argument}', [AdminArgumentController::class, 'update']);
    Route::post('/arguments/bulk', [AdminArgumentController::class, 'bulkStore']);
    Route::delete('/arguments/{argument}', [AdminArgumentController::class, 'destroy']);

    Route::get('/political-parties', [AdminPoliticalPartyController::class, 'index']);
    Route::post('/political-parties', [AdminPoliticalPartyController::class, 'store']);
    Route::patch('/political-parties/{politicalParty}', [AdminPoliticalPartyController::class, 'update']);
});


use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    Mail::raw('Dit is een testmail vanaf WVNL via Mailtrap.', function ($m) {
        $m->to('any@recipient.test')  // mag eender welk adres zijn
            ->subject('WVNL Mailtrap Test');
    });

    return response()->json(['sent' => true]);
});