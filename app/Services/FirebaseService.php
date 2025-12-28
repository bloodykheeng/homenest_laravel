<?php

namespace App\Services;

use App\Traits\LoggableTrait;
use Exception;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;

class FirebaseService
{
    use LoggableTrait;

    protected $messaging;

    public function __construct()
    {
        try {
            // Use storage_path helper to get the full path to the service account JSON file
            $serviceAccountPath = [
                'type' => env('FIREBASE_TYPE'),
                'project_id' => env('FIREBASE_PROJECT_ID'),
                'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
                'private_key' => env('FIREBASE_PRIVATE_KEY'),
                'client_email' => env('FIREBASE_CLIENT_EMAIL'),
                'client_id' => env('FIREBASE_CLIENT_ID'),
                'auth_uri' => env('FIREBASE_AUTH_URI'),
                'token_uri' => env('FIREBASE_TOKEN_URI'),
                'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL'),
                'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
                'universe_domain' => env('FIREBASE_UNIVERSE_DOMAIN'),
            ];

            $factory = (new Factory)->withServiceAccount($serviceAccountPath);
            $this->messaging = $factory->createMessaging();
        } catch (Exception $e) {

            $this->logActivity(
                'firebase_initialization_error',
                "Failed to initialize Firebase service. Error: {$e->getMessage()}",
                [
                    'action' => 'firebase_initialization',
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'stack_trace' => $e->getTraceAsString(),
                ]
            );
            // throw $e;
        }
    }

    public function sendNotification($token, $title, $body, $data = [])
    {
        try {
            $message = CloudMessage::new()
                ->toToken($token)
                ->withNotification(['title' => $title, 'body' => $body])
                ->withDefaultSounds()
                ->withData($data);

            $this->messaging->send($message);

            $this->logActivity(
                'firebase_notification_sent',
                "Successfully sent Firebase notification to token.",
                [
                    'action' => 'send_notification',
                    'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                    'title' => $title,
                    'body' => $body,
                    'data_keys' => array_keys($data),
                    'recipient_token' => substr($token, 0, 20) . '...',
                ]
            );
        } catch (MessagingException $e) {

            $this->logActivity(
                'firebase_notification_error',
                "Failed to send Firebase notification to token. Error: {$e->getMessage()}",
                [
                    'action' => 'send_notification',
                    'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                    'title' => $title,
                    'body' => $body,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'firebase_error_type' => 'MessagingException',
                    'recipient_token' => substr($token, 0, 20) . '...',
                ]
            );
            // throw $e;
        } catch (Exception $e) {

            $this->logActivity(
                'firebase_notification_error',
                "Unexpected error while sending Firebase notification. Error: {$e->getMessage()}",
                [
                    'action' => 'send_notification',
                    'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                    'title' => $title,
                    'body' => $body,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'firebase_error_type' => 'GeneralException',
                    'recipient_token' => substr($token, 0, 20) . '...',
                ]
            );
            // throw $e;
        }
    }

    public function sendNotificationToTopic($topic, $title, $body, $data = [])
    {
        try {
            $imageUrl = env('APP_URL', 'http://localhost:8000') . "/ppda_fb-removebg-preview.png";
            $frontEndUrl = env('FRONTEND_URL', 'http://localhost:3000'); // Deep link URL

            $notification = Notification::create()
                ->withTitle($title)
                ->withBody($body);
            // ->withImageUrl($imageUrl);

            $config = WebPushConfig::fromArray([
                'notification' => [
                    'icon' => $imageUrl,
                ],
                'fcm_options' => [
                    'link' => $frontEndUrl,
                ],
            ]);

            $message = CloudMessage::new()
                ->toTopic($topic)
                ->withNotification($notification)
                ->withDefaultSounds()
                ->withData(array_merge($data, ['image' => $imageUrl]))
                ->withWebPushConfig($config);

            $this->messaging->send($message);

            $this->logActivity(
                'firebase_topic_notification_sent',
                "Successfully sent Firebase notification to topic '{$topic}'.",
                [
                    'action' => 'send_notification_to_topic',
                    'topic' => $topic,
                    'title' => $title,
                    'body' => $body,
                    'data_keys' => array_keys($data),
                    'image_url' => $imageUrl,
                    'frontend_url' => $frontEndUrl,
                    'recipient_topic' => $topic,
                ]
            );
        } catch (MessagingException $e) {

            $this->logActivity(
                'firebase_topic_notification_error',
                "Failed to send Firebase notification to topic '{$topic}'. Error: {$e->getMessage()}",
                [
                    'action' => 'send_notification_to_topic',
                    'topic' => $topic,
                    'title' => $title,
                    'body' => $body,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'firebase_error_type' => 'MessagingException',
                    'recipient_topic' => $topic,
                ]
            );
            // Still log to Laravel's error log as before
            // error_log('Error sending notification: ' . $e->getMessage());
        } catch (Exception $e) {

            $this->logActivity(
                'firebase_topic_notification_error',
                "Unexpected error while sending Firebase notification to topic '{$topic}'. Error: {$e->getMessage()}",
                [
                    'action' => 'send_notification_to_topic',
                    'topic' => $topic,
                    'title' => $title,
                    'body' => $body,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'firebase_error_type' => 'GeneralException',
                    'recipient_topic' => $topic,
                ]
            );
            // error_log('Unexpected error sending notification: ' . $e->getMessage());
        }
    }

    public function subscribeToTopics(string $token, array $topics): void
    {
        foreach ($topics as $topic) {
            try {
                $this->messaging->subscribeToTopic($topic, $token);
                // Log::info("Subscribed {$token} to {$topic}");

                // // Log successful subscription
                // $this->logActivity(
                //     'firebase_topic_subscription_success',
                //     'Successfully subscribed token to Firebase topic',
                //     [
                //         'action' => 'subscribe_to_topic',
                //         'topic'  => $topic,
                //         'token'  => substr($token, 0, 20) . '...', // Partial token for privacy
                //     ]
                // );
            } catch (MessagingException $e) {
                // Log::error("Failed to subscribe {$token} to {$topic}: " . $e->getMessage());

                $this->logActivity(
                    'firebase_topic_subscription_error',
                    'Failed to subscribe token to Firebase topic',
                    [
                        'action' => 'subscribe_to_topic',
                        'topic' => $topic,
                        'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'firebase_error_type' => 'MessagingException',
                    ]
                );
            } catch (Exception $e) {
                // Log::error("Unexpected error subscribing {$token} to {$topic}: " . $e->getMessage());

                $this->logActivity(
                    'firebase_topic_subscription_error',
                    'Unexpected error while subscribing token to Firebase topic',
                    [
                        'action' => 'subscribe_to_topic',
                        'topic' => $topic,
                        'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'firebase_error_type' => 'GeneralException',
                    ]
                );
            }
        }
    }

    /**
     * Unsubscribe a token from multiple topics.
     */
    public function unsubscribeFromTopics(string $token, array $topics)
    {
        try {
            $result = $this->messaging->unsubscribeFromTopics($topics, $token);

            // // Log successful unsubscription
            // $this->logActivity(
            //     'firebase_topic_unsubscription_success',
            //     'Successfully unsubscribed token from Firebase topics',
            //     [
            //         'action'       => 'unsubscribe_from_topics',
            //         'topics'       => $topics,
            //         'token'        => substr($token, 0, 20) . '...', // Partial token for privacy
            //         'topics_count' => count($topics),
            //     ]
            // );

            return $result;
        } catch (MessagingException $e) {

            $this->logActivity(
                'firebase_topic_unsubscription_error',
                'Failed to unsubscribe token from Firebase topics',
                [
                    'action' => 'unsubscribe_from_topics',
                    'topics' => $topics,
                    'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                    'topics_count' => count($topics),
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'firebase_error_type' => 'MessagingException',
                ]
            );

            // Log::error('Firebase unsubscription failed: ' . $e->getMessage());
            // throw $e;
        } catch (Exception $e) {

            $this->logActivity(
                'firebase_topic_unsubscription_error',
                'Unexpected error while unsubscribing token from Firebase topics',
                [
                    'action' => 'unsubscribe_from_topics',
                    'topics' => $topics,
                    'token' => substr($token, 0, 20) . '...', // Partial token for privacy
                    'topics_count' => count($topics),
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'firebase_error_type' => 'GeneralException',
                ]
            );

            // Log::error('Unexpected Firebase unsubscription error: ' . $e->getMessage());
            // throw $e;
        }
    }
}
