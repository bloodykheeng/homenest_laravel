<?php

use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductSubcategoryController;
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

        // ===============================  Product Categories Routes  ===============================
        Route::resource('product-categories', ProductCategoryController::class);
        Route::post('bulk-destroy-product-categories', [ProductCategoryController::class, 'bulkDestroy']);

        // ===============================  Product Subcategories Routes  ===============================
        Route::resource('product-subcategories', ProductSubcategoryController::class);
        Route::post('bulk-destroy-product-subcategories', [ProductSubcategoryController::class, 'bulkDestroy']);

        // ===============================  Products Routes  ===============================
        Route::resource('products', ProductController::class);
        Route::post('bulk-destroy-products', [ProductController::class, 'bulkDestroy']);
        Route::post('products/{id}/update-featured-photo', [ProductController::class, 'updateFeaturedPhoto']);
    });
