<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailTestController extends Controller
{

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function testEmail(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Extract email and message from request
        $email = $request->input('email');
        $messageBody = $request->input('message');

        try {
            // Send the email using the Mail facade
            Mail::send('emails.email_test', ['messageBody' => $messageBody], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email');
            });

            // Return success response
            return response()->json(['status' => 'Email sent successfully'], 200);

        } catch (Exception $e) {
            // Handle any errors that occur during email sending
            return response()->json([
                'status' => 'Email failed to send',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testSendingFirebasePushNotification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'title' => 'required|string',
            'message' => 'required|string',
            'notification_type' => 'required|string',
            'topic' => 'nullable|string',
        ]);

        $email = $request->input('email');
        $title = $request->input('title');
        $message = $request->input('message');
        $notificationType = $request->input('notification_type');
        $topic = $request->input('topic');

        try {
            $user = User::where('email', $email)->first();

            if (!$user || !$user->web_app_firebase_token) {
                return response()->json(['status' => 'User not found or missing push token'], 404);
            }

            if ($notificationType === "topic") {
                // Send topic-based notification
                $this->firebaseService->sendNotificationToTopic($topic, $title, $message);
            } else {
                // Send normal notification using user's Firebase token
                $this->firebaseService->sendNotification($user->web_app_firebase_token, $title, $message);

            }

            return response()->json(['status' => 'Push notification sent successfully'], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'Push notification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserWithRolesByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $approvers = User::role('CSO Approver')
            ->where('cso_id', $user->cso_id)
            ->get();

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(), // Provided by Spatie
            '$approvers' => $approvers,
        ]);
    }

}
