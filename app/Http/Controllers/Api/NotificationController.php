<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailNotificationToUsersJob;
use App\Jobs\SendFirebaseNotificationToUsersJob;
use App\Jobs\SendSmsNotificationToUsersJob;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index(Request $request)
    {
        $query = Notification::with(['createdBy', 'updatedBy', 'users']);

        // Apply filters
        if ($request->has('selectedTypes') && is_array($request->selectedTypes) && count($request->selectedTypes) > 0) {
            $query->whereIn('type', $request->selectedTypes);
        }

        if ($request->has('selectedTargetAudience') && is_array($request->selectedTargetAudience) && count($request->selectedTargetAudience) > 0) {
            $query->whereIn('target_audience', $request->selectedTargetAudience);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->query('search') . '%');
        }

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->query('gender'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('target_audience')) {
            $query->where('target_audience', $request->query('target_audience'));
        }

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            return response()->json(['data' => $query->latest()->paginate($perPage)]);
        }

        return response()->json(['data' => $query->latest()->get()]);
    }

    public function show($id)
    {
        $notification = Notification::with(['createdBy', 'updatedBy', 'users'])->findOrFail($id);
        return response()->json($notification);
    }

    public function getAuthUserNotifications(Request $request)
    {
        try {
            // Unauthenticated users see only "All Users" notifications
            if (!Auth::check()) {
                $query = Notification::with(['createdBy'])
                    ->where('status', 'active')
                    ->where('target_audience', 'All Users')
                    ->where(function ($q) {
                        $q->where('end_date', '>=', now())->orWhereNull('end_date');
                    })
                    ->where(function ($q) {
                        $q->where('start_date', '<=', now())->orWhereNull('start_date');
                    });

                if ($request->has('search')) {
                    $query->where('title', 'like', '%' . $request->query('search') . '%');
                }

                if ($request->has('type')) {
                    $query->where('type', $request->query('type'));
                }

                $totalNotificationsCount = $query->count();
                $query->latest();

                if ($request->boolean('paginate')) {
                    $perPage = $request->get('rowsPerPage', 10);
                    $notifications = $query->paginate($perPage);

                    return response()->json([
                        'data' => $notifications,
                        'unread_notifications_count' => $totalNotificationsCount,
                        'read_notifications_count' => 0,
                        'total_notifications_count' => $totalNotificationsCount,
                    ]);
                }

                $notifications = $query->get();
                return response()->json([
                    'data' => $notifications,
                    'unread_notifications_count' => $totalNotificationsCount,
                    'read_notifications_count' => 0,
                    'total_notifications_count' => $totalNotificationsCount,
                ]);
            }

            $user = User::find(Auth::id());

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Fetch active notifications
            $query = Notification::with(['createdBy'])
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->where('end_date', '>=', now())->orWhereNull('end_date');
                })
                ->where(function ($q) {
                    $q->where('start_date', '<=', now())->orWhereNull('start_date');
                });

            $query->where(function ($mainQuery) use ($user) {
                // 1. All Users notifications
                $mainQuery->where(function ($q) use ($user) {
                    $q->where('target_audience', 'All Users')
                        ->where(function ($genderQ) use ($user) {
                            $genderQ->where('gender', $user->gender)->orWhere('gender', 'Both');
                        });
                });

                // 2. Local targeting (users from Uganda)
                $mainQuery->orWhere(function ($localQuery) use ($user) {
                    $localQuery->where('target_audience', 'Local')
                        ->where(function ($genderQ) use ($user) {
                            $genderQ->where('gender', $user->gender)->orWhere('gender', 'Both');
                        })
                        ->whereHas('users', function ($q) use ($user) {
                            $q->where('users.id', $user->id)
                                ->where('users.citizenship', 'Local');
                        });
                });

                // 3. International targeting (users outside Uganda)
                $mainQuery->orWhere(function ($intlQuery) use ($user) {
                    $intlQuery->where('target_audience', 'International')
                        ->where(function ($genderQ) use ($user) {
                            $genderQ->where('gender', $user->gender)->orWhere('gender', 'Both');
                        })
                        ->whereHas('users', function ($q) use ($user) {
                            $q->where('users.id', $user->id)
                                ->where('users.citizenship', '!=', 'Local');
                        });
                });

                // 4. Specific user targeting
                $mainQuery->orWhere(function ($userQuery) use ($user) {
                    $userQuery->where('target_audience', 'Users')
                        ->where(function ($genderQ) use ($user) {
                            $genderQ->where('gender', $user->gender)->orWhere('gender', 'Both');
                        })
                        ->whereHas('notificationUsers', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                });
            });

            // Apply filters
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->query('search') . '%');
            }

            if ($request->has('type')) {
                $query->where('type', $request->query('type'));
            }

            $totalNotificationsCount = (clone $query)->count();

            $readNotificationsCount = (clone $query)->whereHas('viewedBies', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->count();

            $unreadNotificationsCount = $totalNotificationsCount - $readNotificationsCount;

            $query->with(['viewedBies' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);

            $query->latest();

            if ($request->boolean('paginate')) {
                $perPage = $request->get('rowsPerPage', 10);
                $notifications = $query->paginate($perPage);

                $notifications->getCollection()->transform(function ($notification) use ($user) {
                    $notification->seen = $notification->viewedBies->where('user_id', $user->id)->isNotEmpty();
                    unset($notification->viewedBies);
                    return $notification;
                });

                return response()->json([
                    'data' => $notifications,
                    'unread_notifications_count' => $unreadNotificationsCount,
                    'read_notifications_count' => $readNotificationsCount,
                    'total_notifications_count' => $totalNotificationsCount,
                ]);
            }

            $notifications = $query->get();
            $notifications->transform(function ($notification) use ($user) {
                $notification->seen = $notification->viewedBies->where('user_id', $user->id)->isNotEmpty();
                unset($notification->viewedBies);
                return $notification;
            });

            return response()->json([
                'data' => $notifications,
                'unread_notifications_count' => $unreadNotificationsCount,
                'read_notifications_count' => $readNotificationsCount,
                'total_notifications_count' => $totalNotificationsCount,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url|max:500',
            'type' => 'required|in:User,System,Promotional,Order,Product',
            'gender' => 'required|in:Male,Female,Both',
            'status' => 'required|in:active,inactive',
            'target_audience' => 'required|in:All Users,Local,International,Users',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'users' => 'nullable|array',
            'users.*.id' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'link' => $validated['link'] ?? null,
                'type' => $validated['type'],
                'gender' => $validated['gender'],
                'status' => $validated['status'],
                'target_audience' => $validated['target_audience'],
                'start_date' => $validated['start_date'] ?? now(),
                'end_date' => $validated['end_date'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncNotificationRelationships($notification, $request);

            if ($validated['status'] === 'active') {
                $this->sendNotifications($notification, $request);
            }

            DB::commit();

            Log::info('Notification Created', [
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'created_by' => Auth::user()->name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification created successfully',
                'data' => $notification->load(['createdBy', 'users'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'nullable|url|max:500',
            'type' => 'sometimes|required|in:User,System,Promotional,Order,Product',
            'gender' => 'sometimes|required|in:Male,Female,Both',
            'status' => 'sometimes|required|in:active,inactive',
            'target_audience' => 'sometimes|required|in:All Users,Local,International,Users',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'users' => 'nullable|array',
            'users.*.id' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $notification->status;

            $notification->update(array_merge($validated, [
                'updated_by' => Auth::id(),
            ]));

            $this->smartSyncNotificationRelationships($notification, $request);

            if ($oldStatus === 'inactive' && $validated['status'] === 'active') {
                $this->sendNotifications($notification, $request);
            }

            DB::commit();

            Log::info('Notification Updated', [
                'notification_id' => $notification->id,
                'title' => $notification->title,
                'updated_by' => Auth::user()->name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification updated successfully',
                'data' => $notification->load(['createdBy', 'updatedBy', 'users'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $title = $notification->title;

            $notification->delete();

            Log::info('Notification Deleted', [
                'notification_id' => $id,
                'title' => $title,
                'deleted_by' => Auth::user()->name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:notifications,id',
            ]);

            $notifications = Notification::whereIn('id', $validated['ids'])->get();
            $titles = $notifications->pluck('title')->toArray();

            Notification::whereIn('id', $validated['ids'])->delete();

            Log::info('Notifications Bulk Deleted', [
                'count' => count($validated['ids']),
                'titles' => $titles,
                'deleted_by' => Auth::user()->name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notifications deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $notification = Notification::findOrFail($id);
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Check if already viewed
            $alreadyViewed = $notification->viewedBies()->where('user_id', $user->id)->exists();

            if (!$alreadyViewed) {
                $notification->viewedBies()->create(['user_id' => $user->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function syncNotificationRelationships(Notification $notification, Request $request)
    {
        $targetAudience = $notification->target_audience;

        // Attach specific users if user targeting
        if ($targetAudience === 'Users' && $request->has('users')) {
            $userIds = array_column($request->users, 'id');
            foreach ($userIds as $userId) {
                $notification->notificationUsers()->create(['user_id' => $userId]);
            }
        }
    }

    private function smartSyncNotificationRelationships(Notification $notification, Request $request)
    {
        $targetAudience = $request->target_audience ?? $notification->target_audience;

        // Clear all user relationships
        $notification->notificationUsers()->delete();

        // Re-sync based on target audience
        if ($targetAudience === 'Users' && $request->has('users')) {
            $userIds = array_column($request->users, 'id');
            foreach ($userIds as $userId) {
                $notification->notificationUsers()->create(['user_id' => $userId]);
            }
        }
    }

    private function sendNotifications(Notification $notification, Request $request)
    {
        switch ($notification->target_audience) {
            case 'All Users':
                $this->sendAllUsersNotifications($notification);
                break;
            case 'Local':
                $this->sendLocalNotifications($notification);
                break;
            case 'International':
                $this->sendInternationalNotifications($notification);
                break;
            case 'Users':
                $this->sendUserNotifications($notification, $request);
                break;
        }
    }

    private function sendAllUsersNotifications(Notification $notification)
    {
        $gender = strtolower($notification->gender);
        $genderPrefix = ($gender === 'both') ? 'gender_male_and_female' : 'gender_' . $gender;
        $topic = $genderPrefix . '_all_users';

        $this->firebaseService->sendNotificationToTopic(
            $topic,
            $notification->title,
            $notification->description,
            [
                'type' => 'notifications',
                'notification_type' => $notification->type,
                'notification_id' => $notification->id,
                'link' => $notification->link,
            ]
        );

        Log::info('Notification Sent - All Users', [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'topic' => $topic,
        ]);
    }

    private function sendLocalNotifications(Notification $notification)
    {
        $gender = strtolower($notification->gender);
        $genderPrefix = ($gender === 'both') ? 'gender_male_and_female' : 'gender_' . $gender;
        $topic = $genderPrefix . '_local_users';

        $this->firebaseService->sendNotificationToTopic(
            $topic,
            $notification->title,
            $notification->description,
            [
                'type' => 'notifications',
                'notification_type' => $notification->type,
                'notification_id' => $notification->id,
                'link' => $notification->link,
            ]
        );

        // Also send to local users via email/SMS
        $userIds = User::where('citizenship', 'Local')
            ->when($notification->gender !== 'Both', function ($q) use ($notification) {
                $q->where('gender', $notification->gender);
            })
            ->pluck('id')
            ->toArray();

        if (!empty($userIds)) {
            $this->dispatchNotificationJobs($notification, $userIds);
        }

        Log::info('Notification Sent - Local Users', [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'topic' => $topic,
            'users_count' => count($userIds),
        ]);
    }

    private function sendInternationalNotifications(Notification $notification)
    {
        $gender = strtolower($notification->gender);
        $genderPrefix = ($gender === 'both') ? 'gender_male_and_female' : 'gender_' . $gender;
        $topic = $genderPrefix . '_international_users';

        $this->firebaseService->sendNotificationToTopic(
            $topic,
            $notification->title,
            $notification->description,
            [
                'type' => 'notifications',
                'notification_type' => $notification->type,
                'notification_id' => $notification->id,
                'link' => $notification->link,
            ]
        );

        // Also send to international users via email/SMS
        $userIds = User::where('citizenship', '!=', 'Local')
            ->when($notification->gender !== 'Both', function ($q) use ($notification) {
                $q->where('gender', $notification->gender);
            })
            ->pluck('id')
            ->toArray();

        if (!empty($userIds)) {
            $this->dispatchNotificationJobs($notification, $userIds);
        }

        Log::info('Notification Sent - International Users', [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'topic' => $topic,
            'users_count' => count($userIds),
        ]);
    }

    private function sendUserNotifications(Notification $notification, Request $request)
    {
        if (!$request->has('users')) {
            return;
        }

        $userIds = array_column($request->users, 'id');

        if (empty($userIds)) {
            return;
        }

        $this->dispatchNotificationJobs($notification, $userIds);

        Log::info('Notification Sent - Specific Users', [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'users_count' => count($userIds),
        ]);
    }

    private function dispatchNotificationJobs(Notification $notification, array $userIds)
    {
        $emailDynamicProperties = [
            'notification' => $notification,
            'authUser' => Auth::user(),
        ];

        SendEmailNotificationToUsersJob::dispatch(
            userIds: $userIds,
            subject: $notification->title,
            viewTemplate: 'emails.notifications.general',
            additionalProperties: $emailDynamicProperties,
            createdBy: Auth::user()
        );

        SendFirebaseNotificationToUsersJob::dispatch(
            userIds: $userIds,
            title: $notification->title,
            notificationMessage: $notification->description,
            data: [
                'type' => 'notifications',
                'notification_type' => $notification->type,
                'notification_id' => $notification->id,
                'link' => $notification->link,
            ],
            createdBy: Auth::user()
        );

        SendSmsNotificationToUsersJob::dispatch(
            userIds: $userIds,
            title: $notification->title,
            notificationMessage: $notification->description,
            data: [
                'type' => 'notifications',
                'notification_type' => $notification->type,
                'link' => $notification->link,
            ],
            createdBy: Auth::user()
        );
    }
}
