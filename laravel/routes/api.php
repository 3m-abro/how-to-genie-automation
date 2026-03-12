<?php

use Illuminate\Support\Facades\Route;

// Dashboard API (no auth by default; add middleware in app/Http/Kernel or route group if needed)
Route::get('/dashboard/revenue', [App\Http\Controllers\DashboardController::class, 'revenue'])
    ->name('api.dashboard.revenue');
