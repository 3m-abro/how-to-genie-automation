<?php

use App\Http\Controllers\MissionControlController;
use App\Http\Controllers\N8nWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Mission Control (add auth middleware in app if needed)
Route::get('/mission-control', [MissionControlController::class, 'dashboard'])->name('mission.control');
Route::get('/weekly-summary', [MissionControlController::class, 'weeklySummary'])->name('weekly.summary');
Route::post('/api/quick-action/{action}', [MissionControlController::class, 'quickAction'])->name('api.quick.action');
Route::post('/api/n8n/trigger/{workflow}', [N8nWebhookController::class, 'trigger'])->name('api.n8n.trigger');
Route::get('/api/n8n/status', [N8nWebhookController::class, 'status'])->name('api.n8n.status');
