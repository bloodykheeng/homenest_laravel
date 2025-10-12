<?php

/**
 * 🧠 AI GUIDELINE: User Management Controller
 * ============================================================
 * Manages user accounts with role support (System Admin, Customer)
 * - Email notifications on create/update
 * - Notifies all System Admins when new users are created
 * - Phone validation (12 digits)
 * - Password hashing
 * - Activity logging via LoggableTrait
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    use LoggableTrait;

    /**
     * 📜 index() - List users with optional filters and pagination.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles']);

        // 🔍 Filters
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('username', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('role')) {
            $query->role($request->query('role'));
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->query('gender'));
        }

        if ($request->filled('startDate')) {
            $query->whereDate('created_at', '>=', $request->query('startDate'));
        }

        if ($request->filled('endDate')) {
            $query->whereDate('created_at', '<=', $request->query('endDate'));
        }

        // 📊 Pagination
        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            $data = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Users retrieved successfully',
                'data' => $data,
                'version' => 'v1',
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $data,
            'version' => 'v1',
        ]);
    }

    /**
     * 🆕 store() - Create a new user.
     */
    public function store(Request $request)
    {
        try {
            // Merge allow_notifications boolean conversion
            $request->merge([
                'allow_notifications' => filter_var($request->input('allow_notifications'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'nullable|string|max:255|unique:users,username',
                'phone' => 'nullable|string|size:12|unique:users,phone',
                'gender' => 'nullable|string|in:male,female,other',
                'allow_notifications' => 'nullable|boolean',
                'status' => 'required|in:active,inactive',
                'photo_url' => 'nullable|string',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:System Admin,Customer',
                'mobile_app_firebase_token' => 'nullable|string',
                'admin_dashboard_firebase_token' => 'nullable|string',
            ]);

            DB::beginTransaction();

            $plainPassword = $validated['password'];
            $validated['password'] = Hash::make($plainPassword);

            $user = User::create($validated);

            // Assign role
            $user->assignRole($validated['role']);

            $this->logActivity('user_created', "User '{$user->name}' created with role '{$validated['role']}'.", [
                'user_id' => $user->id,
            ]);

            // Send welcome email to the new user
            $this->sendWelcomeEmail($user, $plainPassword);

            // Notify all System Admins about new user creation
            $this->notifySystemAdmins($user, 'created');

            DB::commit();

            return response()->json(
                [
                    'message' => 'User created successfully',
                    'data' => $user->load('roles'),
                    'version' => 'v1',
                ],
                201
            );
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    /**
     * 👁️ show() - Display a single user.
     */
    public function show($id)
    {
        $user = User::with(['roles'])->find($id);

        if (! $user) {
            return response()->json(
                [
                    'message' => 'The requested user was not found',
                    'error' => 'not_found',
                    'code' => 404,
                    'version' => 'v1',
                ],
                404
            );
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => $user,
            'version' => 'v1',
        ]);
    }

    /**
     * ✍️ update() - Update an existing user.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (! $user) {
                return response()->json(
                    [
                        'message' => 'The requested user was not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            // Merge allow_notifications boolean conversion
            $request->merge([
                'allow_notifications' => filter_var($request->input('allow_notifications'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
                'phone' => 'nullable|string|size:12|unique:users,phone,' . $user->id,
                'gender' => 'nullable|string|in:male,female,other',
                'allow_notifications' => 'nullable|boolean',
                'status' => 'sometimes|in:active,inactive',
                'photo_url' => 'nullable|string',
                'password' => 'nullable|string|min:8',
                'role' => 'sometimes|string|in:System Admin,Customer',
                'mobile_app_firebase_token' => 'nullable|string',
                'admin_dashboard_firebase_token' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            // Update role if provided
            if (isset($validated['role'])) {
                $user->syncRoles([$validated['role']]);
            }

            $this->logActivity('user_updated', "User '{$user->name}' updated.", [
                'user_id' => $user->id,
            ]);

            // Send account update email
            $this->sendAccountUpdateEmail($user);

            // Notify all System Admins about user update
            $this->notifySystemAdmins($user, 'updated');

            DB::commit();

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user->load('roles'),
                'version' => 'v1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                    'version' => 'v1',
                ],
                422
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    /**
     * 🗑️ destroy() - Delete a user.
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (! $user) {
                return response()->json(
                    [
                        'message' => 'The requested user was not found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            DB::beginTransaction();

            $userName = $user->name;
            $user->delete();

            $this->logActivity('user_deleted', "User '{$userName}' deleted.", [
                'user_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'User deleted successfully',
                'version' => 'v1',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    /**
     * 🧹 bulkDestroy() - Delete multiple users at once.
     */
    public function bulkDestroy(Request $request)
    {
        $items = $request->input('itemsToDelete');

        if (! is_array($items) || empty($items)) {
            return response()->json(
                [
                    'message' => 'Invalid or empty data',
                    'error' => 'bad_request',
                    'code' => 400,
                    'version' => 'v1',
                ],
                400
            );
        }

        $ids = array_column($items, 'id');

        try {
            DB::beginTransaction();

            $users = User::whereIn('id', $ids)->get();

            if ($users->isEmpty()) {
                return response()->json(
                    [
                        'message' => 'No matching users found',
                        'error' => 'not_found',
                        'code' => 404,
                        'version' => 'v1',
                    ],
                    404
                );
            }

            $deletedNames = $users->pluck('name')->toArray();
            User::whereIn('id', $ids)->delete();

            $this->logActivity('users_bulk_deleted', 'Deleted: ' . implode(', ', $deletedNames), [
                'ids' => $ids,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bulk delete successful',
                'version' => 'v1',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(
                [
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'code' => 500,
                    'version' => 'v1',
                ],
                500
            );
        }
    }

    /**
     * 📧 Send welcome email to new user
     */
    private function sendWelcomeEmail($user, $plainPassword = null): void
    {
        if (! empty($user->email)) {
            try {
                Mail::send(
                    'emails.users.welcome',
                    [
                        'user' => $user,
                        'plainPassword' => $plainPassword,
                    ],
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Welcome to Homenest');
                    }
                );
            } catch (Exception $e) {
                $this->logActivity('email_sending_failed', "Failed to send welcome email to '{$user->email}'. Error: {$e->getMessage()}", ['user_id' => $user->id]);
            }
        }
    }

    /**
     * 📧 Send account update email to user
     */
    private function sendAccountUpdateEmail($user): void
    {
        if (! empty($user->email)) {
            try {
                Mail::send(
                    'emails.users.account-updated',
                    [
                        'user' => $user,
                    ],
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Your Homenest Account Has Been Updated');
                    }
                );
            } catch (Exception $e) {
                $this->logActivity('email_sending_failed', "Failed to send update email to '{$user->email}'. Error: {$e->getMessage()}", ['user_id' => $user->id]);
            }
        }
    }

    /**
     * 📧 Notify all System Admins about user changes
     */
    private function notifySystemAdmins($user, $action): void
    {
        try {
            $systemAdmins = User::role('System Admin')->get();

            foreach ($systemAdmins as $admin) {
                if (! empty($admin->email) && $admin->id !== $user->id) {
                    Mail::send(
                        'emails.admins.user-notification',
                        [
                            'admin' => $admin,
                            'user' => $user,
                            'action' => $action,
                        ],
                        function ($message) use ($admin, $action) {
                            $message->to($admin->email)->subject("User Account {$action}");
                        }
                    );
                }
            }
        } catch (Exception $e) {
            $this->logActivity('admin_notification_failed', "Failed to notify system admins. Error: {$e->getMessage()}", ['user_id' => $user->id]);
        }
    }
}
