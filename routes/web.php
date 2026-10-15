<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isSuperAdmin()) return redirect()->route('superadmin.dashboard');
        if ($user->isManager())    return redirect()->route('manager.dashboard');
        return redirect()->route('tickets.index');
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
    Route::get('/documentation/conversations', [\App\Http\Controllers\DocumentationController::class, 'getConversations'])->name('documentation.conversations');
    Route::get('/documentation/conversations/{id}', [\App\Http\Controllers\DocumentationController::class, 'getConversation'])->name('documentation.conversations.show');
    Route::delete('/documentation/conversations/{id}', [\App\Http\Controllers\DocumentationController::class, 'destroyConversation'])->name('documentation.conversations.destroy');
    Route::delete('/documentation/conversations', [\App\Http\Controllers\DocumentationController::class, 'clearAllConversations'])->name('documentation.conversations.clear-all');

    // Pengecekan Harian
    Route::get('/daily-check', [\App\Http\Controllers\DailyCheckController::class, 'index'])->name('daily-check.index');
    Route::post('/daily-check/save-items', [\App\Http\Controllers\DailyCheckController::class, 'saveItems'])->name('daily-check.save-items');
    Route::post('/daily-check/items/{item}/resolve', [\App\Http\Controllers\DailyCheckController::class, 'resolveItem'])->name('daily-check.resolve-item');
    Route::post('/daily-check/create-ticket', [\App\Http\Controllers\DailyCheckController::class, 'createTicketFromItems'])->name('daily-check.create-ticket');

    // Master Data
    Route::prefix('master')->name('master.')->group(function () {
        // Toko
        Route::get('/stores', [\App\Http\Controllers\MasterDataController::class, 'storesIndex'])->name('stores');
        Route::post('/stores', [\App\Http\Controllers\MasterDataController::class, 'storeStore'])->name('stores.store');
        Route::put('/stores/{store}', [\App\Http\Controllers\MasterDataController::class, 'storeUpdate'])->name('stores.update');
        Route::delete('/stores/{store}', [\App\Http\Controllers\MasterDataController::class, 'storeDestroy'])->name('stores.destroy');
        Route::post('/stores/import-csv', [\App\Http\Controllers\MasterDataController::class, 'storeImportCsv'])->name('stores.import-csv');

        // Device
        Route::get('/devices', [\App\Http\Controllers\MasterDataController::class, 'devicesIndex'])->name('devices');
        Route::post('/devices', [\App\Http\Controllers\MasterDataController::class, 'deviceStore'])->name('devices.store');
        Route::put('/devices/{device}', [\App\Http\Controllers\MasterDataController::class, 'deviceUpdate'])->name('devices.update');
        Route::delete('/devices/{device}', [\App\Http\Controllers\MasterDataController::class, 'deviceDestroy'])->name('devices.destroy');
        Route::post('/devices/import-csv', [\App\Http\Controllers\MasterDataController::class, 'deviceImportCsv'])->name('devices.import-csv');
    });

    // Super Admin Routes
    Route::middleware('role:superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('dashboard');

        // Companies
        Route::get('/companies', [\App\Http\Controllers\SuperAdminController::class, 'companies'])->name('companies');
        Route::post('/companies', [\App\Http\Controllers\SuperAdminController::class, 'storeCompany'])->name('companies.store');
        Route::put('/companies/{company}', [\App\Http\Controllers\SuperAdminController::class, 'updateCompany'])->name('companies.update');
        Route::delete('/companies/{company}', [\App\Http\Controllers\SuperAdminController::class, 'destroyCompany'])->name('companies.destroy');
        Route::post('/gemini/test-connection', [\App\Http\Controllers\SuperAdminController::class, 'testGeminiConnection'])->name('gemini.test');

        // Managers
        Route::get('/managers', [\App\Http\Controllers\SuperAdminController::class, 'managers'])->name('managers');
        Route::post('/managers', [\App\Http\Controllers\SuperAdminController::class, 'storeManager'])->name('managers.store');
        Route::delete('/managers/{user}', [\App\Http\Controllers\SuperAdminController::class, 'destroyManager'])->name('managers.destroy');
    });

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
