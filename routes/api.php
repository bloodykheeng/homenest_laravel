<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * 🧭 API Version 1 Routes
 * ================================================
 */
Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {

        // ===============================  Users Routes  ===============================
        Route::resource('users', UserController::class);
        Route::post('bulk-destroy-users', [UserController::class, 'bulkDestroy']);
    });
