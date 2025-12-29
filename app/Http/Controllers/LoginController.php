<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\HandlePhotoTrait;
use App\Traits\LoggableTrait;
use App\Traits\SendSmsNotificationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class LoginController extends Controller
{
    use LoggableTrait, SendSmsNotificationTrait, HandlePhotoTrait;

    /**
     * Public registration (Customer registration)
     */
    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->merge([
                'allow_notifications' => filter_var($request->input('allow_notifications'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'nullable|string|min:3|max:20|unique:users,username',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'gender' => 'nullable|string|in:Male,Female,Prefer not to say',
                'password' => 'required|string|min:8|confirmed',
                'allow_notifications' => 'nullable|boolean',
                'citizenship' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'postal_code' => 'nullable|string|max:20',

                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $plainPassword = $validated['password'];
            $validated['password'] = Hash::make($validated['password']);
            $validated['status'] = 'active'; // Auto-activate customer accounts

            // Handle photo upload
            if ($request->hasFile('photo.file_path')) {
                $photo = $validated['photo']['file_path'];
                $validated['photo_url'] = $this->handlePhotoUpload($photo, 'user_photos');
                unset($validated['photo']);
            }

            // Remove password_confirmation
            unset($validated['password_confirmation']);

            // Create user
            $user = User::create($validated);

            // Assign Customer role
            $customerRole = Role::where('name', 'Customer')->first();
            if ($customerRole) {
                $user->assignRole($customerRole);
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Send welcome notification
            if ($user->status === 'active') {
                $this->sendCustomerWelcomeNotifications($user, $plainPassword);
            }

            // Log activity
            $this->logActivity(
                'customer_registered',
                "New customer '{$user->name}' registered on HomeNest.",
                ['user_id' => $user->id, 'email' => $user->email, 'phone' => $user->phone]
            );

            DB::commit();

            $response = [
                'message' => 'Registration successful. Welcome to HomeNest!',
                'id' => $user->id,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'phone' => $user->phone,
                'photo_url' => $user->photo_url,
                'role' => $user->role,
            ];

            return response()->json($response, 201);
        } catch (Exception $e) {
            DB::rollBack();

            if (isset($validated['photo_url'])) {
                $this->deletePhoto($validated['photo_url']);
            }

            $this->logActivity(
                'customer_registration_failed',
                "Failed to register customer. Error: {$e->getMessage()}",
                ['error_line' => $e->getLine(), 'request' => $request->except(['password', 'password_confirmation', 'photo'])]
            );

            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin dashboard login
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|min:3|max:255',
                'password' => 'required|string',
            ]);

            $identifier = $request->input('email');

            if (!$this->isValidIdentifier($identifier)) {
                return response()->json([
                    'message' => 'Please enter a valid email address, phone number, or username',
                ], 422);
            }

            $fieldType = $this->determineFieldType($identifier);
            $credentials = [
                $fieldType => $identifier,
                'password' => $request->input('password'),
            ];

            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = User::with([
                'createdBy',
                'updatedBy',
            ])->where($fieldType, $identifier)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if ($user->status !== 'active') {
                return response()->json(['message' => 'Account is not active'], 403);
            }

            // Admin dashboard roles
            $acceptedRoles = [
                'System Admin',
                'Vendor',
            ];

            $userRole = $user->role ?? 'Customer';

            if (!in_array($userRole, $acceptedRoles)) {
                return response()->json(['message' => 'Unauthorized role for admin dashboard'], 403);
            }

            $user->update(['lastlogin' => now()]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $this->logActivity(
                'admin_login',
                "User '{$user->name}' logged into HomeNest admin dashboard.",
                ['user_id' => $user->id, 'role' => $userRole]
            );

            $response = [
                'message' => 'Hi ' . $user->name . ', welcome to HomeNest admin dashboard',
                'id' => $user->id,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'name' => $user->name,
                'photo_url' => $user->photo_url,
                'lastlogin' => $user->lastlogin,
                'email' => $user->email,
                'username' => $user->username,
                'gender' => $user->gender,
                'status' => $user->status,
                'allow_notifications' => $user->allow_notifications,
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
        } catch (Exception $e) {
            $this->logActivity(
                'admin_login_failed',
                "Admin login failed. Error: {$e->getMessage()}",
                ['error_line' => $e->getLine()]
            );

            return response()->json(['message' => 'An error occurred while signing in', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mobile app login
     */
    public function applogin(Request $request)
    {
        try {
            $request->validate([
                'credential' => 'required|string',
                'password' => 'required|string',
                'device_token' => 'nullable|string',
            ]);

            $loginField = filter_var($request->input('credential'), FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

            if (!Auth::attempt([$loginField => $request->input('credential'), 'password' => $request->input('password')])) {
                return response()->json(['message' => 'Invalid credentials provided'], 401);
            }

            $user = User::where($loginField, $request->input('credential'))
                ->with([
                    'createdBy',
                    'updatedBy',
                ])
                ->firstOrFail();

            if ($user->status !== 'active') {
                return response()->json(['message' => 'Account is not active'], 403);
            }

            // Mobile app roles (all roles can access mobile app)
            $mobileRoles = [
                'Admin',
                'Vendor',
                'Customer',
            ];
            $userRole = $user->role ?? 'Customer';

            if (!in_array($userRole, $mobileRoles)) {
                return response()->json(['message' => 'User does not have mobile app access'], 403);
            }

            $updateData = ['lastlogin' => now()];
            if ($request->filled('device_token')) {
                $updateData['mobile_app_firebase_token'] = $request->input('device_token');
            }
            $user->update($updateData);

            $token = $user->createToken('mobile_auth_token')->plainTextToken;

            $this->logActivity(
                'mobile_login',
                "User '{$user->name}' logged into HomeNest mobile app.",
                ['user_id' => $user->id, 'role' => $userRole]
            );

            $response = [
                'message' => 'Welcome to HomeNest, ' . $user->name,
                'id' => $user->id,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'name' => $user->name,
                'photo_url' => $user->photo_url,
                'lastlogin' => $user->lastlogin,
                'email' => $user->email,
                'username' => $user->username,
                'allow_notifications' => $user->allow_notifications,
                'status' => $user->status,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'citizenship' => $user->citizenship,
                'city' => $user->city,
                'address' => $user->address,
                'postal_code' => $user->postal_code,

                'role' => $user->role,
                'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
            ];

            return response()->json($response);
        } catch (Exception $e) {
            $this->logActivity(
                'mobile_login_failed',
                "Mobile app login failed. Error: {$e->getMessage()}",
                ['error_line' => $e->getLine()]
            );

            return response()->json(['message' => 'An error occurred during mobile login', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Web app login (for customers shopping on website)
     */
    public function weblogin(Request $request)
    {
        try {
            $request->validate([
                'credential' => 'required|string',
                'password' => 'required|string',
                'device_token' => 'nullable|string',
            ]);

            $loginField = filter_var($request->input('credential'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            if (!Auth::attempt([$loginField => $request->input('credential'), 'password' => $request->input('password')])) {
                return response()->json(['message' => 'Invalid credentials provided'], 401);
            }

            $user = User::where($loginField, $request->input('credential'))->firstOrFail();

            if ($user->status !== 'active') {
                return response()->json(['message' => 'Account is not active'], 403);
            }

            $updateData = ['lastlogin' => now()];
            if ($request->filled('device_token')) {
                $updateData['web_app_firebase_token'] = $request->input('device_token');
            }
            $user->update($updateData);

            $token = $user->createToken('web_auth_token')->plainTextToken;

            $this->logActivity(
                'web_login',
                "User '{$user->name}' logged into HomeNest web app.",
                ['user_id' => $user->id, 'role' => $user->role]
            );

            $response = [
                'message' => 'Welcome back, ' . $user->name,
                'id' => $user->id,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'name' => $user->name,
                'photo_url' => $user->photo_url,
                'lastlogin' => $user->lastlogin,
                'email' => $user->email,
                'username' => $user->username,
                'allow_notifications' => $user->allow_notifications,
                'status' => $user->status,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'citizenship' => $user->citizenship,
                'city' => $user->city,
                'address' => $user->address,
                'postal_code' => $user->postal_code,
                'role' => $user->role,
            ];

            return response()->json($response);
        } catch (Exception $e) {
            $this->logActivity(
                'web_login_failed',
                "Web app login failed. Error: {$e->getMessage()}",
                ['error_line' => $e->getLine()]
            );

            return response()->json(['message' => 'An error occurred during login', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Logout user and revoke current token
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        $this->logActivity(
            'user_logout',
            "User '{$user->name}' logged out from HomeNest.",
            ['user_id' => $user->id]
        );

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out from HomeNest'], 200);
    }

    /**
     * Helper: Send customer welcome notifications
     */
    private function sendCustomerWelcomeNotifications($user, $plainPassword)
    {
        if (!empty($user->email)) {
            try {
                Mail::send('emails.users.customer_registration', [
                    'user' => $user,
                    'plainPassword' => $plainPassword,
                ], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject("Welcome to HomeNest - Your Account is Ready!");
                });
            } catch (Exception $e) {
                $this->logActivity(
                    'email_sending_failed',
                    "Failed to send customer registration email to '{$user->email}'. Error: {$e->getMessage()}",
                    ['user_id' => $user->id]
                );
            }
        }

        if (!empty($user->phone)) {
            try {
                $this->sendSmsNotification(
                    'single',
                    $user->phone,
                    "Welcome to HomeNest, {$user->name}! Your account has been created successfully. Start shopping for quality home essentials today!",
                    'HomeNest'
                );
            } catch (Exception $e) {
                $this->logActivity(
                    'sms_sending_failed',
                    "Failed to send SMS to '{$user->phone}'. Error: {$e->getMessage()}",
                    ['user_id' => $user->id]
                );
            }
        }
    }

    /**
     * Validate identifier
     */
    private function isValidIdentifier($identifier)
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        if (preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $identifier) && strlen(preg_replace('/[^0-9]/', '', $identifier)) >= 7) {
            return true;
        }

        if (preg_match('/^[a-zA-Z0-9._]{3,}$/', $identifier)) {
            return true;
        }

        return false;
    }

    /**
     * Determine field type
     */
    private function determineFieldType($identifier)
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        if (preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $identifier) && strlen(preg_replace('/[^0-9]/', '', $identifier)) >= 7) {
            return 'phone';
        }

        return 'username';
    }
}
