<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isManager() ? redirect()->route('manager.dashboard') : redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Auth routes (Breeze)
require __DIR__ . '/auth.php';

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets/analyze', [TicketController::class, 'analyze'])->name('tickets.analyze');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::get('/tickets/{ticket}/image/{index?}', [TicketController::class, 'image'])->name('tickets.image');
    Route::get('/tickets/export/download', [TicketController::class, 'export'])->name('tickets.export');

    // Documentation
    Route::get('/documentation', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('documentation');
    Route::post('/documentation/chat', [\App\Http\Controllers\DocumentationController::class, 'chat'])->name('documentation.chat');
    Route::get('/documentation/chat/{chat}/image/{index?}', [\App\Http\Controllers\DocumentationController::class, 'image'])->name('documentation.image');
    Route::delete('/documentation/chat/{id}', [\App\Http\Controllers\DocumentationController::class, 'destroy'])->name('documentation.destroy');
    Route::delete('/documentation/chats', [\App\Http\Controllers\DocumentationController::class, 'clearAll'])->name('documentation.clear-all');



    // Manager Routes
    Route::middleware('role:manager')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [\App\Http\Controllers\ManagerController::class, 'users'])->name('users');
        Route::post('/users', [\App\Http\Controllers\ManagerController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\ManagerController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\ManagerController::class, 'destroyUser'])->name('users.destroy');
        
        Route::get('/analysis', [\App\Http\Controllers\MonthlyAnalysisController::class, 'index'])->name('analysis.index');
        Route::post('/analysis/create', [\App\Http\Controllers\MonthlyAnalysisController::class, 'createByAi'])->name('analysis.create');
        Route::get('/analysis/{id}', [\App\Http\Controllers\MonthlyAnalysisController::class, 'show'])->name('analysis.show');

        Route::get('/settings', [\App\Http\Controllers\ManagerController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\ManagerController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/reset-gemini', [\App\Http\Controllers\ManagerController::class, 'resetGeminiQuota'])->name('settings.reset-gemini');
    });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
