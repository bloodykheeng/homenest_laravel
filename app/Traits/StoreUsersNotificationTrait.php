<?php

namespace App\Traits;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait StoreUsersNotificationTrait
{

    use LoggableTrait;

    /**
     * Store a notification for specific users
     *
     * @param array $userIds Array of user IDs to notify
     * @param string $title Notification title
     * @param string $description Notification description
     * @param string $type Notification type (defaults to 'System')
     * @param \DateTime|string|null $startDate Start date (optional)
     * @param \DateTime|string|null $endDate End date (optional)
     * @param int|null $durationInDays Duration in days (used if endDate not provided)
     * @param User|null $createdBy User who created the notification (defaults to Auth user)
     * @return Notification
     * @throws \Exception
     */
    protected function storeUsersNotification(
        array $userIds,
        string $title,
        string $description,
        string $type = 'System',
        $startDate = null,
        $endDate = null,
        ?int $durationInDays = null,
        ?User $createdBy = null
    ): Notification {
        DB::beginTransaction();

        try {
            // Validate that userIds array is not empty
            if (empty($userIds)) {
                throw new Exception('User IDs array cannot be empty');
            }

            // Remove duplicates
            $userIds = array_unique($userIds);

            // Fetch users with their details for validation and logging
            $users = User::whereIn('id', $userIds)
                ->select('id', 'name', 'email', 'phone')
                ->get();

            // Validate that all user IDs exist
            if ($users->count() !== count($userIds)) {
                $foundUserIds = $users->pluck('id')->toArray();
                $invalidUserIds = array_diff($userIds, $foundUserIds);

                throw new Exception(
                    'Invalid user ID(s) provided: ' . implode(', ', $invalidUserIds) .
                        '. These user IDs do not exist in the database.'
                );
            }

            $notificationStartDate = null;
            $notificationEndDate = null;

            if ((isset($startDate) && isset($endDate)) || isset($durationInDays)) {

                // Determine start date
                $notificationStartDate = $startDate ? Carbon::parse($startDate) : Carbon::now();

                // Determine end date
                if ($endDate) {
                    $notificationEndDate = Carbon::parse($endDate);
                } elseif ($durationInDays) {
                    $notificationEndDate = Carbon::parse($notificationStartDate)->addDays($durationInDays);
                } else {
                    $notificationEndDate = Carbon::parse($notificationStartDate)->addDays(30); // Default 30 days
                }

                // Validate that end date is after start date
                if ($notificationEndDate->lessThanOrEqualTo($notificationStartDate)) {
                    throw new Exception('End date must be after start date');
                }
            }



            // Determine creator
            $creator = $createdBy ?? Auth::user();
            $creatorId = $creator ? $creator->id : null;

            // Create the notification
            $notification = Notification::create([
                'title'           => $title,
                'description'     => $description,
                'type'            => $type,
                'gender'          => 'Both',
                'status'          => 'active',
                'target_audience' => 'Users',
                'scope'           => null,
                'start_date'      => $notificationStartDate,
                'end_date'        => $notificationEndDate,
                'created_by'      => $creatorId,
                'updated_by'      => $creatorId,
            ]);

            // Attach user relationships
            foreach ($userIds as $userId) {
                $notification->notificationUsers()->create([
                    'user_id'    => $userId,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ]);
            }

            DB::commit();

            // Log activity if Loggable trait is available
            if (method_exists($this, 'logActivity')) {
                // Prepare user details for logging
                $userDetails = $users->map(function ($user) {
                    return [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ];
                })->toArray();

                // Create readable user list for message
                $userList = $users->map(function ($user) {
                    return "{$user->name} ({$user->email})";
                })->implode(', ');

                // Truncate user list if too long
                if (strlen($userList) > 200) {
                    $displayCount = min(3, $users->count());
                    $userList = $users->take($displayCount)->map(function ($user) {
                        return "{$user->name} ({$user->email})";
                    })->implode(', ');

                    if ($users->count() > $displayCount) {
                        $userList .= '... and ' . ($users->count() - $displayCount) . ' more';
                    }
                }

                $this->logActivity(
                    'User Notification Stored',
                    "Notification '{$title}' was stored for " . count($userIds) . " users" .
                        ($creator ? " by {$creator->name} ({$creator->email})" : "") .
                        ". Users: {$userList}",
                    [
                        'notification_id'    => $notification->id,
                        'notification_title' => $title,
                        'notification_description' => $description,
                        'user_ids'           => $userIds,
                        'users_count'        => count($userIds),
                        'users_details'      => $userDetails,
                        'created_by_id'      => $creatorId,
                        'created_by_name'    => $creator?->name,
                        'created_by_email'   => $creator?->email,
                        'start_date'         => $notificationStartDate->toDateTimeString(),
                        'end_date'           => $notificationEndDate->toDateTimeString(),
                        'duration_days'      => $notificationStartDate->diffInDays($notificationEndDate),
                    ],
                    $creator
                );
            }

            return $notification;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
