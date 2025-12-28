<?php

namespace App\Jobs;

use App\Models\User;
use App\Traits\SendSmsNotificationTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSmsNotificationToUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SendSmsNotificationTrait;

    protected string $title;
    protected string $notificationMessage;
    protected array $userIds;
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
        string $title,
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

    public function handle(): void
    {
        $users = User::whereIn('id', $this->userIds)->get();

        foreach ($users as $user) {
            $phoneNumber = $user->phone ?? null;

            if (! $phoneNumber) {
                $this->logActivity(
                    'sms_skipped',
                    "No phone number found for {$user->name} ({$user->email})",
                    [
                        'user_id' => $user->id
                    ],
                    $this->createdBy
                );
                continue;
            }

            $this->sendSmsNotification(
                'single',
                $phoneNumber,
                $this->notificationMessage,
                $this->data['sender'] ?? null,
                $this->data['sendtime'] ?? null,
                $this->createdBy
            );
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $e): void
    {
        $users = User::whereIn('id', $this->userIds)->pluck('name', 'email')->map(fn($name, $email) => "{$name} ({$email})")->implode(', ');

        $this->logActivity(
            'sms_error',
            "Failed to send SMS to {$users}.\nError: {$e->getMessage()}.\nData: " . json_encode($this->data),
            [
                'user_ids' => $this->userIds
            ],
            $this->createdBy
        );
    }
}
