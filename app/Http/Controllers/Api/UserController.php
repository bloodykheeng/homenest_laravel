<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\HandlePhotoTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    use HandlePhotoTrait;

    public function index(Request $request)
    {
        $query = User::with([
            "createdBy",
            "updatedBy",
        ]);

        // Filter by role
        if ($request->filled('role')) {
            $role = $request->query('role');
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filter by multiple roles
        if ($request->filled('roles') && is_array($request->query('roles'))) {
            $roles = $request->query('roles');
            $query->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);
            });
        }

        // Filter by country
        if ($request->filled('country')) {
            $query->where('country', $request->query('country'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->query('gender'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->latest()->paginate($perPage);
            return response()->json(['data' => $data]);
        }

        $data = $query->latest()->get();
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->merge([
                'allow_notifications' => filter_var($request->input('allow_notifications'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'username' => 'nullable|string|min:3|max:20|unique:users,username',
                'phone' => 'nullable|string|max:20|unique:users,phone',
                'gender' => 'nullable|string|in:Male,Female,Prefer not to say',
                'password' => 'required|string|min:8',
                'allow_notifications' => 'nullable|boolean',
                'status' => 'required|in:active,inactive',
                'country' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'postal_code' => 'nullable|string|max:20',
                'mobile_app_firebase_token' => 'nullable|string',
                'web_app_firebase_token' => 'nullable|string',
                'role' => 'required|in:Admin,Customer,Vendor',

                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $role = $validated['role'];

            if (empty($role)) {
                return response()->json(['message' => 'Role is required.'], 422);
            }

            $plainPassword = $validated['password'];
            $validated['password'] = Hash::make($validated['password']);
            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();

            // Handle photo upload
            if ($request->hasFile('photo.file_path')) {
                $photo = $validated['photo']['file_path'];
                $validated['photo_url'] = $this->handlePhotoUpload($photo, 'user_photos');
                unset($validated['photo']);
            }

            unset($validated['role']);

            $user = User::create($validated);

            // Assign role
            if ($role) {
                $user->assignRole($role);
            }

            // Send notifications
            if ($user->status === 'active') {
                $this->sendUserCreatedNotifications($user, $plainPassword);
            }

            Log::info('User Created', [
                'user_id' => $user->id,
                'role' => $role,
                'name' => $user->name,
                'created_by' => Auth::user()->name ?? 'System',
            ]);

            DB::commit();

            return response()->json(['message' => 'User created successfully', 'data' => $user], 201);
        } catch (Exception $e) {
            DB::rollBack();

            if (isset($validated['photo_url'])) {
                $this->deletePhoto($validated['photo_url']);
            }

            Log::error('User Creation Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'An error occurred while creating the user.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function show($id)
    {
        $user = User::with([
            "createdBy",
            "updatedBy",
        ])->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $photoUpdated = false;

        try {
            $user = User::findOrFail($id);

            $request->merge([
                'allow_notifications' => filter_var($request->input('allow_notifications'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
                'username' => 'sometimes|nullable|string|min:3|max:20|unique:users,username,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->id,
                'gender' => 'sometimes|nullable|string|in:Male,Female,Prefer not to say',
                'password' => 'sometimes|nullable|string|min:8',
                'allow_notifications' => 'sometimes|nullable|boolean',
                'status' => 'sometimes|nullable|in:active,inactive',
                'country' => 'sometimes|nullable|string|max:100',
                'city' => 'sometimes|nullable|string|max:100',
                'address' => 'sometimes|nullable|string|max:500',
                'postal_code' => 'sometimes|nullable|string|max:20',
                'mobile_app_firebase_token' => 'sometimes|nullable|string',
                'web_app_firebase_token' => 'sometimes|nullable|string',
                'role' => 'sometimes|nullable|in:Admin,Customer,Vendor',

                'photo' => 'nullable|array',
                'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);

            $role = $validated['role'] ?? $user->roles->first()?->name;

            if (empty($role)) {
                return response()->json(['message' => 'Role is required.'], 422);
            }

            $passwordChanged = false;
            if (isset($validated['password']) && !empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
                $passwordChanged = true;
            } else {
                unset($validated['password']);
            }

            $validated['updated_by'] = Auth::id();

            // Photo
            $oldPhotoPath = $user->getRawOriginal('photo_url');
            if ($request->hasFile('photo.file_path')) {
                if ($oldPhotoPath) {
                    $this->deletePhoto($oldPhotoPath);
                }
                $photo = $validated['photo']['file_path'];
                $validated['photo_url'] = $this->handlePhotoUpload($photo, 'user_photos');
                $photoUpdated = true;
                unset($validated['photo']);
            }

            unset($validated['role']);

            $user->update($validated);

            // Update role
            if (isset($request->role)) {
                $user->syncRoles([$role]);
            }

            // Send email notification on update
            if ($user->status === 'active') {
                $this->sendUserUpdatedNotifications($user, $passwordChanged);
            }

            Log::info('User Updated', [
                'user_id' => $user->id,
                'password_changed' => $passwordChanged,
                'updated_by' => Auth::user()->name ?? 'System',
            ]);

            DB::commit();

            return response()->json(['message' => 'User updated successfully', 'data' => $user]);
        } catch (Exception $e) {
            DB::rollBack();

            if ($photoUpdated && isset($validated['photo_url'])) {
                $this->deletePhoto($validated['photo_url']);
            }

            Log::error('User Update Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function updateUserProfile(Request $request)
    {
        $user = Auth::user();

        $user = User::where('id', Auth::user()->id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->merge([
            'allow_notifications' => filter_var($request->input('allow_notifications'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'username' => 'sometimes|nullable|string|min:3|max:20|unique:users,username,' . $user->id,
            'current_password' => 'required|string|min:6',
            'new_password' => 'nullable|string|min:6',
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . $user->id,
            'gender' => 'nullable|in:Male,Female,Prefer not to say',
            'allow_notifications' => 'nullable|boolean',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',

            'photo' => 'nullable|array',
            'photo.file_path' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // Verify current password
        if (!Hash::check($validatedData['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        DB::beginTransaction();

        $photoUpdated = false;

        try {
            $updateData = array_filter([
                'name' => $validatedData['name'] ?? null,
                'email' => $validatedData['email'] ?? null,
                'username' => $validatedData['username'] ?? null,
                'phone' => $validatedData['phone'] ?? null,
                'gender' => $validatedData['gender'] ?? null,
                'allow_notifications' => $validatedData['allow_notifications'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'postal_code' => $validatedData['postal_code'] ?? null,
                'updated_by' => $user->id,
            ], function ($value) {
                return $value !== null;
            });

            $passwordChanged = false;
            if (!empty($validatedData['new_password'])) {
                $updateData['password'] = Hash::make($validatedData['new_password']);
                $passwordChanged = true;
            }

            // Handle Photo
            $oldPhotoPath = $user->getRawOriginal('photo_url');
            if ($request->hasFile('photo.file_path')) {
                if ($oldPhotoPath) {
                    $this->deletePhoto($oldPhotoPath);
                }
                $photo = $validatedData['photo']['file_path'];
                $updateData['photo_url'] = $this->handlePhotoUpload($photo, 'user_photos');
                $photoUpdated = true;
            }

            $user->update($updateData);

            if ($user->status === 'active') {
                // Email Notification
                if (!empty($user->email)) {
                    try {
                        Mail::send('emails.users.profile_updated', [
                            'user' => $user,
                            'password_changed' => $passwordChanged,
                            'system_name' => 'HomeNest',
                        ], function ($message) use ($user) {
                            $message->to($user->email)
                                ->subject('Your HomeNest profile has been updated');
                        });
                    } catch (Exception $e) {
                        Log::error('Email Sending Failed - Profile Update', [
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('User Profile Updated', [
                'user_id' => $user->id,
                'name' => $user->name,
                'password_changed' => $passwordChanged,
                'photo_changed' => $photoUpdated,
            ]);

            DB::commit();

            $safeUserData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'allow_notifications' => $user->allow_notifications,
                'status' => $user->status,
                'photo_url' => $user->photo_url,
                'country' => $user->country,
                'city' => $user->city,
                'address' => $user->address,
                'postal_code' => $user->postal_code,
                'updated_at' => $user->updated_at,
            ];

            return response()->json([
                'message' => ($passwordChanged && $photoUpdated)
                    ? 'Profile, password and photo updated successfully'
                    : ($passwordChanged
                        ? 'Profile and password updated successfully'
                        : ($photoUpdated
                            ? 'Profile and photo updated successfully'
                            : 'Profile updated successfully')),
                'data' => $safeUserData,
                'password_changed' => $passwordChanged,
                'photo_changed' => $photoUpdated,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            if ($photoUpdated && isset($updateData['photo_url'])) {
                $this->deletePhoto($updateData['photo_url']);
            }

            Log::error('User Profile Update Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating your profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);

            if ($user->photo_url) {
                $this->deletePhoto($user->getRawOriginal('photo_url'));
            }

            $userName = $user->name;
            $user->delete();

            Log::info('User Deleted', [
                'user_id' => $id,
                'name' => $userName,
                'deleted_by' => Auth::user()->name ?? 'System',
            ]);

            DB::commit();

            return response()->json(['message' => 'User deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while deleting the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $itemsToDelete = $request->input('itemsToDelete');

            if (!is_array($itemsToDelete) || empty($itemsToDelete)) {
                return response()->json(['message' => 'Invalid or empty users data'], 400);
            }

            $userIds = array_column($itemsToDelete, 'id');

            if (empty($userIds)) {
                return response()->json(['message' => 'No valid user IDs found'], 400);
            }

            $users = User::whereIn('id', $userIds)->get();

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No matching users found'], 404);
            }

            $deletedUserNames = $users->pluck('name')->toArray();

            foreach ($users as $user) {
                if ($user->photo_url) {
                    $this->deletePhoto($user->getRawOriginal('photo_url'));
                }
            }

            User::whereIn('id', $userIds)->delete();

            Log::info('Users Bulk Deleted', [
                'user_ids' => $userIds,
                'names' => implode(', ', $deletedUserNames),
                'deleted_by' => Auth::user()->name ?? 'System',
            ]);

            DB::commit();

            return response()->json(['message' => 'Users deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while deleting users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendUserCreatedNotifications($user, $plainPassword)
    {
        if (!empty($user->email)) {
            try {
                Mail::send('emails.users.user_account_created', [
                    'user' => $user,
                    'plainPassword' => $plainPassword,
                ], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject("Welcome to HomeNest - Your Account Has Been Created");
                });
            } catch (Exception $e) {
                Log::error('Email Sending Failed - Account Created', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendUserUpdatedNotifications($user, $passwordChanged)
    {
        if (!empty($user->email)) {
            try {
                Mail::send('emails.users.user_account_updated', [
                    'user' => $user,
                    'password_changed' => $passwordChanged,
                ], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject("Your HomeNest Account Has Been Updated");
                });
            } catch (Exception $e) {
                Log::error('Email Sending Failed - Account Updated', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
