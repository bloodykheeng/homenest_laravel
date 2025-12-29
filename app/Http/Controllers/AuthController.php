<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $firebaseService;

    public function __construct()
    {
        $this->firebaseService = app(FirebaseService::class);
    }

    /**
     * Check login status for authenticated users
     */
    public function checkLoginStatus(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User is not logged in'], 401);
        }

        $user = User::with([
            'createdBy',
            'updatedBy',
        ])->where('id', Auth::user()->id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->status !== 'active') {
            Auth::logout();
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Your account is not active. Please contact HomeNest support to resolve this.',
                'error' => 'Account inactive',
            ], 401);
        }

        $response = [
            'message' => 'Hi ' . $user->name . ', welcome to HomeNest',
            'id' => $user->id,
            'name' => $user->name,
            'lastlogin' => $user->lastlogin,
            'email' => $user->email,
            'username' => $user->username,
            'gender' => $user->gender,
            'status' => $user->status,
            'allow_notifications' => $user->allow_notifications,
            'photo_url' => $user->photo_url,
            'phone' => $user->phone,
            'citizenship' => $user->citizenship,
            'city' => $user->city,
            'address' => $user->address,
            'postal_code' => $user->postal_code,
            'email_verified_at' => $user->email_verified_at,

            'role' => $user->role,
            'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
            'created_by' => $user->createdBy,
            'updated_by' => $user->updatedBy,
        ];

        return response()->json($response);
    }

    /**
     * Save Firebase token for push notifications
     */
    public function saveFirebaseToken(Request $request)
    {
        $request->validate([
            'firebase_token' => 'required|string',
            'type' => 'required|in:mobile,web',
        ]);

        $user = Auth::user();
        if (!isset($user)) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $tokenColumn = $request->type === 'mobile'
            ? 'mobile_app_firebase_token'
            : 'web_app_firebase_token';

        $newToken = $request->firebase_token;
        $oldToken = $user->{$tokenColumn};

        if (!empty($oldToken) && $oldToken === $newToken) {
            return response()->json([
                'message' => "Token already up-to-date for {$request->type}",
            ]);
        }

        // Build topics
        $topics = [];

        // Gender-based topics
        $gender = strtolower($user->gender ?? 'both');
        $genderPrefix = ($gender === 'both' || empty($gender) || $gender === 'prefer not to say')
            ? 'gender_male_and_female'
            : 'gender_' . str_replace(' ', '_', $gender);

        // All Users topic
        $topics[] = $genderPrefix . '_all_users';

        // Location-based topics (Local/International)
        if (!empty($user->citizenship)) {
            if ($user->citizenship === 'Uganda') {
                $topics[] = $genderPrefix . '_local_users';
            } else {
                $topics[] = $genderPrefix . '_international_users';
            }
        }

        // Role-based topic
        if (!empty($user->role) && $user->role !== 'No Role') {
            $topics[] = "role_" . strtolower(str_replace(' ', '_', $user->role));
        }

        // citizenship-specific topic
        if (!empty($user->citizenship)) {
            $topics[] = "citizenship_" . strtolower(str_replace(' ', '_', $user->citizenship));
        }

        // City-specific topic
        if (!empty($user->city)) {
            $topics[] = "city_" . strtolower(str_replace(' ', '_', $user->city));
        }

        try {
            if (!empty($oldToken)) {
                $this->firebaseService->unsubscribeFromTopics($oldToken, $topics);
            }

            $user->update([$tokenColumn => $newToken]);

            $this->firebaseService->subscribeToTopics($newToken, $topics);

            return response()->json([
                'message' => "Token updated for {$request->type}, unsubscribed old token, and subscribed successfully",
                'topics' => $topics,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update token and subscribe to topics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check mobile app login status
     */
    public function checkAppLoginStatus()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json(['message' => 'Welcome back to HomeNest, ' . $user->name], 200);
        } else {
            return response()->json(['message' => 'Token is invalid. Please login again'], 401);
        }
    }

    /**
     * Check web app login status
     */
    public function checkWebLoginStatus()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json(['message' => 'Welcome back, ' . $user->name], 200);
        } else {
            return response()->json(['message' => 'Session expired. Please login again'], 401);
        }
    }

    /**
     * Get authenticated user details
     */
    public function getUserDetails()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'User is not authenticated'], 401);
        }

        $user = User::with([
            'createdBy',
            'updatedBy',
        ])->where('id', Auth::user()->id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'status' => $user->status,
            'photo_url' => $user->photo_url,
            'citizenship' => $user->citizenship,
            'city' => $user->city,
            'address' => $user->address,
            'postal_code' => $user->postal_code,
            'allow_notifications' => $user->allow_notifications,
            'role' => $user->role,
            'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Refresh user session
     */
    public function refreshSession()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Session expired'], 401);
        }

        $user = Auth::user();

        // Generate new token
        $token = $user->createToken('refreshed_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Session refreshed successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Verify email (placeholder for email verification flow)
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // TODO: Implement email verification logic
        // This is a placeholder for future implementation

        return response()->json([
            'message' => 'Email verification endpoint - to be implemented',
        ], 501);
    }

    /**
     * Request password reset (placeholder)
     */
    public function requestPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // TODO: Implement password reset request logic
        // This is a placeholder for future implementation

        return response()->json([
            'message' => 'Password reset request endpoint - to be implemented',
        ], 501);
    }
}
