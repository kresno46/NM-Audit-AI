<?php

// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return redirect()->route('dashboard');
})->name('home');

    Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/audit-history', [DashboardController::class, 'auditHistory'])->name('audit.history');
    
    // Audit Management
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/select-employee', [AuditController::class, 'selectEmployee'])->name('select-employee');
        Route::post('/start', [AuditController::class, 'startAudit'])->name('start');
        Route::get('/interview/{sessionId}', [AuditController::class, 'interview'])->name('interview');
        Route::post('/begin/{sessionId}', [AuditController::class, 'beginInterview'])->name('begin');
        Route::post('/complete/{sessionId}', [AuditController::class, 'completeAudit'])->name('complete');
        Route::get('/result/{sessionId}', [AuditController::class, 'result'])->name('result');
        Route::post('/override/{sessionId}', [AuditController::class, 'overrideRecommendation'])->name('override');
        Route::get('/export/{sessionId}', [AuditController::class, 'exportPdf'])->name('export');
        Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::post('/audit/answer/{sessionId}', [AuditController::class, 'submitAnswer'])->name('audit.answer');
        Route::post('/finish/{sessionId}', [AuditController::class, 'finish'])->name('finish');


    });
    
    
    // Reports
     Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::resource('/users', UserController::class);

    });

    
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
