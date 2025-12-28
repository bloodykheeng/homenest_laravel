<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Http;

trait SendSmsNotificationTrait
{
    use LoggableTrait;

    /**
     * Send SMS Notification.
     *
     * @param string $recipientType single | multiple
     * @param string|array $recipient
     * @param string $message
     * @param string|null $sender
     * @param string|null $sendtime
     * @return bool
     */
    public function sendSmsNotification($recipientType, $recipient, $message, $sender = null, $sendtime = null)
    {
        try {
            // Handle recipients
            if ($recipientType === 'multiple' && is_array($recipient)) {
                $recipient = implode(',', $recipient);
            }

            if ($recipientType === 'single' && is_string($recipient) === false) {
                throw new Exception("Recipient must be a string for single type.");
            }

            $queryParams = [
                'message' => $message,
                'recipient' => $recipient,
                'account' => env('SMS_ACCOUNT_ID'),
                'authorization' => env('SMS_AUTH_CODE'),
            ];

            if ($sender) {
                $queryParams['sender'] = $sender;
            }

            if ($sendtime) {
                $queryParams['sendtime'] = $sendtime;
            }

            $response = Http::get(env('SMS_API_URL'), $queryParams);

            // Log activity
            $this->logActivity('sms', 'SMS sent to ' . $recipient, [
                'message' => $message,
                'recipient' => $recipient,
                'response' => $response->body(),
            ]);

            return $response->successful();
        } catch (Exception $e) {

            $this->logActivity('sms_error', "failed to send sms to " . $recipient . "Error: {$e->getMessage()}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
