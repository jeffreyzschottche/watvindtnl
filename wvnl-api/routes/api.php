<?php

use App\Http\Controllers\Admin\AdminArgumentController;
use App\Http\Controllers\Admin\AdminIssueController;
use App\Http\Controllers\Admin\AdminPoliticalPartyController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Cms\ContentController;
use App\Http\Controllers\Cms\ReportSettingsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DevelopmentReportController;
use App\Http\Controllers\IssueArgsController;
use App\Http\Controllers\NewsArticleController;
use App\Http\Controllers\PoliticalCompassController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserIssueController;
use App\Http\Controllers\VoteReportController;
use App\Services\Reports\CompassTypes;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registration');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:password-reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset');
Route::post('/contact', ContactController::class)->middleware('throttle:contact');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::delete('/me', [UserController::class, 'destroy'])->middleware('throttle:account-deletion');
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword']);
    Route::get('/issues/pending', [UserIssueController::class, 'index']);
    Route::get('/me/votes', [UserIssueController::class, 'history']);
    Route::get('/me/reports/dev-access', [DevelopmentReportController::class, 'access']);
    Route::post('/me/reports/dev', [DevelopmentReportController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/me/reports/dev/{id}', [DevelopmentReportController::class, 'show'])->whereUuid('id');
    Route::get('/me/reports/dev/{id}/pdf', [DevelopmentReportController::class, 'download'])->whereUuid('id');
    Route::get('/me/compass-progress', [VoteReportController::class, 'progress']);
    Route::get('/me/reports', [VoteReportController::class, 'index']);
    Route::get('/me/reports/{id}', [VoteReportController::class, 'show']);
    Route::get('/me/reports/{id}/pdf', [VoteReportController::class, 'download'])->middleware('throttle:reports');
    Route::post('/me/reports/orders', [VoteReportController::class, 'store'])->middleware('throttle:reports');
    Route::get('/me/political-compass', [PoliticalCompassController::class, 'show']);
    Route::post('/me/political-compass', [PoliticalCompassController::class, 'store'])->middleware('throttle:reports');
    Route::post('/issues/{issue}/vote', [UserIssueController::class, 'vote'])->middleware('throttle:voting');
    Route::post('/issues/{issue}/report', [ReportController::class, 'reportIssue']);
    Route::post('/arguments/{argument}/report', [ReportController::class, 'reportArgument']);
});

Route::get('/issues-args', [IssueArgsController::class, 'index']);
Route::get('/statements/{issue:slug}', [IssueArgsController::class, 'show']);
Route::get('/issues-args/{issue}', [IssueArgsController::class, 'show']);
Route::get('/news-articles', [NewsArticleController::class, 'index']);
Route::get('/news-articles/{slug}', [NewsArticleController::class, 'show']);
Route::get('/issues/{issue}/news-article', [NewsArticleController::class, 'showByIssue']);

Route::middleware(['auth:sanctum', 'admin.account', 'throttle:admin'])->prefix('admin')->group(function () {
    Route::post('/issues/{issue}/news-article', [NewsArticleController::class, 'generateForIssue'])->middleware('throttle:reports');
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

// Separate content-only credentials: never accept an ordinary app/admin login here.
Route::prefix('cms/content')->middleware(['cms.content:read', 'throttle:admin'])->group(function () {
    Route::get('/', [ContentController::class, 'overview']);
    Route::get('/statements', [ContentController::class, 'statements']);
    Route::get('/statements/{issue}', [ContentController::class, 'statement']);
    Route::get('/motions', [ContentController::class, 'motions']);
    Route::get('/compass/proposals', [ContentController::class, 'compassProposals']);
    Route::get('/compass/weights', [ContentController::class, 'exportCompassWeights']);
    Route::middleware('cms.content:write')->group(function () {
        Route::post('/compass/proposals/generate', [ContentController::class, 'generateCompassProposals'])->middleware('throttle:cms-compass-proposals');
        Route::post('/compass/proposals/approve', [ContentController::class, 'approveCompassProposals']);
        Route::post('/compass/proposals/{id}/approve', [ContentController::class, 'approveCompassProposal'])->whereNumber('id');
        Route::post('/compass/proposals/{id}/reject', [ContentController::class, 'rejectCompassProposal'])->whereNumber('id');
        Route::post('/parties/seed', [ContentController::class, 'seedParties']);
        Route::post('/compass/seed', [ContentController::class, 'seedCompass']);
        Route::post('/compass/weights', [ContentController::class, 'importCompassWeights']);
        Route::post('/parties', [ContentController::class, 'saveParty']);
        Route::patch('/parties/{party}', [ContentController::class, 'saveParty']);
        Route::post('/statements', [ContentController::class, 'saveStatement']);
        Route::post('/motions', [ContentController::class, 'saveMotion']);
        Route::post('/import', [ContentController::class, 'import']);
        Route::post('/motions/sync', [ContentController::class, 'syncMotions'])->middleware('throttle:cms-motion-sync');
    });
});

Route::get('/reports/product', [VoteReportController::class, 'product']);
Route::get('/reports/examples', fn () => CompassTypes::examples());
Route::post('/reports/stripe-webhook', [VoteReportController::class, 'webhook']);

Route::prefix('cms/reports')->middleware(['cms.content:read,reports', 'throttle:admin'])->group(function () {
    Route::get('/settings', [ReportSettingsController::class, 'show']);
    Route::get('/stats', [ReportSettingsController::class, 'stats']);
    Route::post('/estimate', [ReportSettingsController::class, 'estimate']);
    Route::post('/settings', [ReportSettingsController::class, 'save'])->middleware('cms.content:write,reports');
});
