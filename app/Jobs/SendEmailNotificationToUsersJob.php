<?php

namespace App\Jobs;

use App\Models\User;
use App\Traits\LoggableTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotificationToUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, LoggableTrait;

    protected array $userIds;
    protected string $subject;
    protected string $viewTemplate;
    protected array $additionalProperties;
    protected $createdBy;

    /**
     * Create a new job instance.
     *
     * @param array       $userIds
     * @param string      $subject
     * @param string      $viewTemplate
     * @param array       $additionalProperties
     * @param \App\Models\User|null $createdBy
     */
    public function __construct(
        array $userIds,
        string $subject = 'New Notification',
        string $viewTemplate = 'emails.user_notification',
        array $additionalProperties = [],
        $createdBy = null
    ) {
        $this->userIds = $userIds;
        $this->subject = $subject;
        $this->viewTemplate = $viewTemplate;
        $this->additionalProperties = $additionalProperties;
        $this->createdBy = $createdBy;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $users = User::whereIn('id', $this->userIds)->get();

        foreach ($users as $user) {
            if (!empty($user->email)) {

                // Email data sent to the view
                $emailData = array_merge([
                    'user' => $user,
                ], $this->additionalProperties);

                Mail::send($this->viewTemplate, $emailData, function ($message) use ($user) {
                    $message->to($user->email)->subject($this->subject);
                });

                $this->logActivity('email_notification', 'Email sent successfully', [
                    'email'   => $user->email,
                    'subject' => $this->subject,
                ], $this->createdBy);
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $e
     */
    public function failed(Throwable $e)
    {
        // Format list of emails if possible
        $users = User::whereIn('id', $this->userIds)->pluck('email')->toArray();
        $emails = implode(', ', $users);

        $this->logActivity(
            'email_notification_error',
            "Failed to send email to {$emails}.\nError: {$e->getMessage()}.\nData: " . json_encode($this->additionalProperties),
            [
                'error' => $e->getMessage(),
                'user_ids' => $this->userIds,
            ],
            $this->createdBy
        );
    }
}
