<?php
// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuditApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('audit')->group(function () {
        Route::get('/sessions', [AuditApiController::class, 'getSessions']);
        Route::get('/sessions/{sessionId}', [AuditApiController::class, 'getSessionDetails']);
        Route::post('/sessions', [AuditApiController::class, 'createSession']);
        Route::post('/sessions/{sessionId}/complete', [AuditApiController::class, 'completeSession']);
        Route::get('/statistics', [AuditApiController::class, 'getStatistics']);
    });
});
        