<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationViewedBy;
use App\Traits\LoggableTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationViewedByController extends Controller
{
    use LoggableTrait;

    /**
     * Get all users who have viewed a specific notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationViewedBies(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
            'search'          => 'nullable|string',
        ]);

        // Build query
        $query = NotificationViewedBy::with(['user', 'createdBy'])
            ->where('notification_id', $validated['notification_id']);

        // Apply search if provided
        if ($request->has('search') && !empty($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Get total views count before pagination
        $totalViews = $query->count();

        // Get the notification details
        $notification = Notification::find($validated['notification_id']);

        if ($request->boolean('paginate')) {
            $perPage = $request->get('rowsPerPage', 10);
            return response()->json([
                'data'         => $query->latest()->paginate($perPage),
                'totalViews'   => $totalViews,
                'notification' => $notification,
            ]);
        }

        return response()->json([
            'data'         => $query->latest()->get(),
            'totalViews'   => $totalViews,
            'notification' => $notification,
        ]);
    }

    /**
     * Mark a notification as viewed by the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsViewed(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'notification_id' => 'required|integer|exists:notifications,id',
            ]);

            $notificationId = $validated['notification_id'];

            $notification = Notification::findOrFail($notificationId);
            $user         = Auth::user();

            // Check if already viewed
            $viewed = NotificationViewedBy::where('notification_id', $notificationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$viewed) {
                NotificationViewedBy::create([
                    'notification_id' => $notificationId,
                    'user_id'         => $user->id,
                    'created_by'      => $user->id,
                    'updated_by'      => $user->id,
                ]);

                $this->logActivity(
                    'Notification Viewed',
                    "Notification '{$notification->title}' was viewed by {$user->name}",
                    [
                        'notification_id'    => $notificationId,
                        'notification_title' => $notification->title,
                        'user_id'            => $user->id,
                        'user_name'          => $user->name,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as viewed',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification already viewed',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as viewed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
