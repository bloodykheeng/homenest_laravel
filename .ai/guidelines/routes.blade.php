<?php
/**
 * 🧭 AI GUIDELINE: Laravel API Routes Structure
 * ============================================
 * This file defines the base structure and conventions for defining
 * API routes for RESTful controllers in Laravel.
 *
 * ✅ Every resource should:
 * - Use Route::resource() for standard CRUD endpoints
 * - Have a separate bulk destroy route (POST)
 * - Use proper naming conventions for endpoints
 * - Be grouped under API version prefix (e.g., v1)
 * - Support middleware (e.g., auth:sanctum)
 *
 * 📂 File:
 * routes/api.php
 *
 * 📌 Naming Convention:
 * - Resource route: `Route::resource('model-names', ModelNameController::class);`
 * - Bulk delete route: `Route::post('bulk-destroy-model-names', [ModelNameController::class, 'bulkDestroy']);`
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentVisitController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\ExampleController; // 📝 Example

Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        /**
         * 🧍 Agent Visits
         * ------------------------------------------------
         * GET    /agent-visits            → index
         * POST   /agent-visits            → store
         * GET    /agent-visits/{id}       → show
         * PUT    /agent-visits/{id}       → update
         * DELETE /agent-visits/{id}       → destroy
         * POST   /bulk-destroy-agent-visits → bulkDestroy
         */
        Route::resource('agent-visits', AgentVisitController::class);
        Route::post('bulk-destroy-agent-visits', [AgentVisitController::class, 'bulkDestroy']);

        /**
         * 📡 Channels
         * ------------------------------------------------
         * GET    /channels
         * POST   /channels
         * GET    /channels/{id}
         * PUT    /channels/{id}
         * DELETE /channels/{id}
         * POST   /bulk-destroy-channels
         */
        Route::resource('channels', ChannelController::class);
        Route::post('bulk-destroy-channels', [ChannelController::class, 'bulkDestroy']);

        /**
         * 📝 Example Resource (Template)
         * ------------------------------------------------
         * GET    /examples
         * POST   /examples
         * GET    /examples/{id}
         * PUT    /examples/{id}
         * DELETE /examples/{id}
         * POST   /bulk-destroy-examples
         */
        Route::resource('examples', ExampleController::class);
        Route::post('bulk-destroy-examples', [ExampleController::class, 'bulkDestroy']);
    });
