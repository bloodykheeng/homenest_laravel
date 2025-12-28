<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FirebaseService;
use App\Traits\LoggableTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendFirebaseNotificationToUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggableTrait;

    protected array $userIds;
    protected string $title;
    protected string $notificationMessage;
    protected array $data;
    protected $createdBy;

    /**
     * @param array $userIds
     * @param string $title
     * @param string $notificationMessage
     * @param array $data
     * @param mixed $createdBy
     */
    public function __construct(
        array $userIds,
        string $title = 'New Notification',
        string $notificationMessage,
        array $data = [],
        $createdBy = null
    ) {
        $this->userIds             = array_unique($userIds);
        $this->title               = $title;
        $this->notificationMessage = $notificationMessage;
        $this->data                = $data;
        $this->createdBy           = $createdBy;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $users = User::whereIn('id', $this->userIds)->get();
        $firebaseService = app(FirebaseService::class);

        foreach ($users as $user) {

            // ---------------------- MOBILE TOKEN ----------------------
            if (!empty($user->device_token)) {
                // try {
                $firebaseService->sendNotification(
                    $user->device_token,
                    $this->title,
                    $this->notificationMessage,
                    $this->data
                );

                $this->logActivity(
                    'firebase_notification_sent',
                    "Firebase notification sent to {$user->name} ({$user->email})",
                    [
                        'user_id' => $user->id,
                        'title'   => $this->title,
                        'message' => $this->notificationMessage,
                        'data'    => $this->data
                    ],
                    $this->createdBy
                );
                // } catch (Throwable $e) {
                //     $this->logActivity(
                //         'firebase_notification_failed_for_user',
                //         "Failed to send Firebase notification to {$user->name} ({$user->email}). Error: {$e->getMessage()}",
                //         [
                //             'user_id' => $user->id
                //         ],
                //         $this->createdBy
                //     );
                // }
            }

            // ---------------------- WEB TOKEN ----------------------
            if (!empty($user->web_app_firebase_token)) {
                // try {
                $firebaseService->sendNotification(
                    $user->web_app_firebase_token,
                    $this->title,
                    $this->notificationMessage,
                    $this->data
                );

                $this->logActivity(
                    'firebase_web_notification_sent',
                    "Firebase web notification sent to {$user->name} ({$user->email})",
                    [
                        'user_id' => $user->id,
                        'title'   => $this->title,
                        'message' => $this->notificationMessage,
                        'data'    => $this->data
                    ],
                    $this->createdBy
                );
                // } catch (Throwable $e) {
                //     $this->logActivity(
                //         'firebase_web_notification_failed_for_user',
                //         "Failed to send Firebase web notification to {$user->name} ({$user->email}). Error: {$e->getMessage()}",
                //         [
                //             'user_id' => $user->id
                //         ],
                //         $this->createdBy
                //     );
                // }
            }
        }
    }

    /**
     * Handle catastrophic job failure.
     */
    public function failed(Throwable $e)
    {
        $emails = User::whereIn('id', $this->userIds)->pluck('email')->implode(', ');

        $this->logActivity(
            'firebase_notification_error',
            "Job failed while sending Firebase notification to {$emails}.\nError: {$e->getMessage()}.\nData: " . json_encode($this->data),
            [
                'user_ids' => $this->userIds
            ],
            $this->createdBy
        );
    }
}
