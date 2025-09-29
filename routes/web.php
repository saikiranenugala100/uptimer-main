<?php

use App\Http\Controllers\UptimeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [UptimeController::class, 'index'])->name('home');

Route::prefix('api')->group(function () {
    Route::get('clients/{client}/websites', [UptimeController::class, 'getClientWebsites']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
