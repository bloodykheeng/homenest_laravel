<?php

namespace App\Http\Controllers;

use App\Models\SocialAuthProvider;
use App\Models\User;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SocialAuthController extends Controller
{
    use LoggableTrait;

    /**
     * Handle social OAuth login / registration from the frontend.
     * Accepts provider credentials, finds or creates a user, and returns a Sanctum token.
     */
    public function handleSocialLogin(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:google,github,facebook',
            'provider_id' => 'required|string',
            'provider_access_token' => 'nullable|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'photo_url' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $social = SocialAuthProvider::where('provider', $validated['provider'])
                ->where('provider_id', $validated['provider_id'])
                ->first();

            if ($social) {
                $user = $social->user;

                $social->update([
                    'provider_token' => $validated['provider_access_token'] ?? null,
                    'updated_by' => $social->user_id,
                ]);
            } elseif (! empty($validated['email'])) {
                $user = User::where('email', $validated['email'])->first();

                if (! $user) {
                    $user = User::create([
                        'name' => $validated['name'] ?? 'User',
                        'email' => $validated['email'],
                        'photo_url' => $validated['photo_url'] ?? null,
                        'status' => 'active',
                        'password' => null,
                    ]);

                    $customerRole = Role::where('name', 'Customer')->first();
                    if ($customerRole) {
                        $user->assignRole($customerRole);
                    }

                    $this->logActivity(
                        'social_user_registered',
                        "New user '{$user->name}' registered via {$validated['provider']}.",
                        ['user_id' => $user->id, 'provider' => $validated['provider']]
                    );
                }

                SocialAuthProvider::create([
                    'user_id' => $user->id,
                    'provider' => $validated['provider'],
                    'provider_id' => $validated['provider_id'],
                    'provider_token' => $validated['provider_access_token'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            } else {
                DB::rollBack();

                return response()->json([
                    'message' => 'Email is required when linking a new social provider',
                    'error' => 'email_required',
                ], 422);
            }

            if ($user->status !== 'active') {
                DB::rollBack();

                return response()->json([
                    'message' => 'Your account is not active. Please contact HomeNest support.',
                    'error' => 'account_inactive',
                ], 403);
            }

            $user->update(['lastlogin' => now()]);

            $token = $user->createToken('social_auth_token')->plainTextToken;

            DB::commit();

            $this->logActivity(
                'social_login',
                "User '{$user->name}' logged in via {$validated['provider']}.",
                ['user_id' => $user->id, 'provider' => $validated['provider']]
            );

            return response()->json([
                'message' => 'Welcome to HomeNest, '.$user->name,
                'id' => $user->id,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'phone' => $user->phone,
                'photo_url' => $user->photo_url,
                'gender' => $user->gender,
                'status' => $user->status,
                'allow_notifications' => $user->allow_notifications,
                'citizenship' => $user->citizenship,
                'city' => $user->city,
                'address' => $user->address,
                'postal_code' => $user->postal_code,
                'lastlogin' => $user->lastlogin,
                'role' => $user->role,
                'permissions' => $user->getAllPermissions()->pluck('name') ?? [],
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            $this->logActivity(
                'social_login_failed',
                "Social login failed via {$validated['provider']}. Error: {$e->getMessage()}",
                ['error_line' => $e->getLine()]
            );

            return response()->json([
                'message' => 'An error occurred during social login',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
