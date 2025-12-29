<?php

use App\Http\Controllers\Api\ActivityLogsController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationViewedByController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductReviewController;
use App\Http\Controllers\Api\ProductSubcategoryController;
use App\Http\Controllers\Api\RolesAndPermissionsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OtpPasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;





Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::Resource('faqs', FaqController::class)->only(['index'])->middleware('optional_auth');

// =============================== optional auth routes ==================================
Route::group(
    ['middleware' => ['optional_auth']],
    function () {

        // Login Routes
        Route::post('/login', [LoginController::class, 'login']); // Admin dashboard
        Route::post('/applogin', [LoginController::class, 'applogin']); // Mobile app

        Route::Resource('faqs', FaqController::class)->only(['index']);


        //========== email testing =============================
        Route::post('test-email', [EmailTestController::class, 'testEmail']);
        Route::post('testFirebasePushNotification', [EmailTestController::class, 'testSendingFirebasePushNotification']);

        //======================= password reset with otp ===============================
        Route::post('password-get-otp', [OtpPasswordResetController::class, 'getOtpForPasswordReset']);
        Route::post('password-validate-otp', [OtpPasswordResetController::class, 'validateOtp']);
        Route::post('password-reset-with-otp', [OtpPasswordResetController::class, 'resetPasswordWithOtp']);
    }
);


// ========================= private routes ===========================
Route::group(
    ['middleware' => ['auth:sanctum']],
    function () {


        //======================= faqs =============================
        Route::Resource('faqs', FaqController::class)->except(['index']);
        Route::post('bulk-destroy-faqs', [FaqController::class, 'bulkDestroy']);

        // ===============================  Product Categories Routes  ===============================
        Route::resource('product-categories', ProductCategoryController::class);
        Route::post('bulk-destroy-product-categories', [ProductCategoryController::class, 'bulkDestroy']);

        // ===============================  Product Subcategories Routes  ===============================
        Route::resource('product-subcategories', ProductSubcategoryController::class);
        Route::post('bulk-destroy-product-subcategories', [ProductSubcategoryController::class, 'bulkDestroy']);

        // ===============================  Products Routes  ===============================
        Route::resource('products', ProductController::class);
        Route::post('bulk-destroy-products', [ProductController::class, 'bulkDestroy']);

        // ===============================  Products Reviews  ===============================
        Route::resource('product-reviews', ProductReviewController::class);
        Route::post('bulk-destroy-product-reviews', [ProductReviewController::class, 'bulkDestroy']);


        // ====================== Get AuthUser Notifications ======================
        Route::get('getAuthUserNotifications', [NotificationController::class, 'getAuthUserNotifications']);

        // ====================== Notification Viewed By ======================
        Route::post('markNotificationAsViewed', [NotificationViewedByController::class, 'markAsViewed']);
        Route::get('getNotificationViewedBies', [NotificationViewedByController::class, 'getNotificationViewedBies']);

        // ====================== Notifications CRUD ======================
        Route::apiResource('notifications', NotificationController::class);
        Route::post('bulk-destroy-notifications', [NotificationController::class, 'bulkDestroy']);


        // Auth Status Checks
        Route::get('check-login-status', [AuthController::class, 'checkLoginStatus']);
        Route::get('check-app-login-status', [AuthController::class, 'checkAppLoginStatus']);

        // Firebase Token Management
        Route::post('save-firebase-token', [AuthController::class, 'saveFirebaseToken']);

        // Logout
        Route::post('logout', [LoginController::class, 'logout']);

        //======================= users =============================
        Route::Resource('users', UserController::class);
        Route::post('bulk-destroy-users', [UserController::class, 'bulkDestroy']);
        Route::post('postToUpdateUserProfile', [UserController::class, 'updateUserProfile']);

        //================= Roles AND Permisions============================
        Route::get('/roles', [RolesAndPermissionsController::class, 'getAssignableRoles']);
        Route::get('roles-with-modified-permissions', [RolesAndPermissionsController::class, 'getRolesWithModifiedPermissions']);
        Route::post('sync-permissions-to-role', [RolesAndPermissionsController::class, 'syncPermissionsToRole']);
        Route::Resource('users-roles', RolesAndPermissionsController::class);

        // ================== Queue and jobs managements =======================
        Route::get('/jobs', [JobController::class, 'getQueuedJobs']);
        Route::get('/failed-jobs', [JobController::class, 'getFailedJobs']);
        Route::get('/job-stats', [JobController::class, 'getJobStats']);
        Route::post('/bulk-destroy-jobs', [JobController::class, 'bulkDestroyJobs']);
        Route::post('/bulk-destroy-failed-jobs', [JobController::class, 'bulkDestroyFailedJobs']);

        //=================== system logs =================================================
        Route::get('activity-logs', [ActivityLogsController::class, 'index']);
        Route::post('bulk-destroy-activity-logs', [ActivityLogsController::class, 'bulkDestroy']);
    }
);
