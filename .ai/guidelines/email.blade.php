<?php
/**
 * 🧠 AI GUIDELINE: Laravel Email Notifications (TailwindCSS)
 * ==================================================
 * Purpose:
 * - Standardized email sending for Laravel applications.
 * - Use TailwindCSS classes for styling.
 * - Error handling via try/catch with logging.
 * - Organize templates by entity/action.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Traits\LoggableTrait;
use Exception;

class EmailGuidelineController extends Controller
{
    use LoggableTrait;

    /**
     * Send email notification to user (Account Created)
     *
     * @param \App\Models\User $user
     * @param string|null $plainPassword
     */
    public function sendUserAccountCreatedEmail($user, $plainPassword = null)
    {
        if (!empty($user->email)) {
            try {
                Mail::send(
                    'emails.users.user-account-created',
                    [
                        'user' => $user,
                        'plainPassword' => $plainPassword,
                    ],
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Your Account Has Been Created');
                    },
                );
            } catch (Exception $e) {
                $this->logActivity('email_sending_failed', "Failed to send email to '{$user->email}'. Error: {$e->getMessage()}", ['user_id' => $user->id]);
            }
        }
    }
}
?>

<!-- resources/views/emails/users/user-account-created.blade.php -->

@php
    // Fallback for Boost tooling or missing $user
    $user =
        $user ??
        (object) [
            'name' => 'Valued User',
            'email' => 'user@example.com',
            'status' => 'Active',
        ];
    $plainPassword = $plainPassword ?? 'temporary123';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden mt-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-center py-6">
            <h1 class="text-2xl font-bold">🏠 House of Plastics</h1>
            <p class="text-sm mt-1">Admin Dashboard Portal</p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <h2 class="text-xl font-semibold text-center mb-4">Welcome, {{ $user->name }}!</h2>
            <p class="mb-4 text-gray-700">
                Your admin account has been successfully created. You now have access to the dashboard to manage
                operations efficiently.
            </p>

            <!-- Account Details -->
            <div class="bg-gray-50 border-l-4 border-indigo-500 rounded p-4 mb-4">
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Full Name:</span>
                    <span class="font-mono text-gray-600">{{ $user->name }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Email:</span>
                    <span class="font-mono text-gray-600">{{ $user->email }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Temporary Password:</span>
                    <span class="font-mono text-gray-600">{{ $plainPassword }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Account Status:</span>
                    <span
                        class="px-2 py-1 bg-green-100 text-green-800 rounded">{{ ucfirst($user->status ?? 'Active') }}</span>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="bg-yellow-100 border-l-4 border-yellow-400 p-4 mb-4 text-yellow-800">
                <strong>🛡️ Important:</strong> Please change your password immediately after first login. Do not share
                your credentials.
            </div>

            <!-- Dashboard Button -->
            <div class="text-center mb-4">
                <a href="#"
                    class="inline-block bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold py-2 px-6 rounded hover:opacity-90 transition">
                    🚀 Access Dashboard
                </a>
            </div>

            <!-- Features -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2">🎯 What You Can Do:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Manage inventory and product catalogs</li>
                    <li>Track orders and customer interactions</li>
                    <li>Generate reports and analytics</li>
                    <li>Coordinate with team members</li>
                    <li>Access real-time insights</li>
                    <li>Configure system settings</li>
                </ul>
            </div>

            <p class="text-gray-700 mt-4">If you encounter any issues, please contact our support team.</p>
        </div>

        <!-- Footer -->
        <div class="bg-gray-800 text-white text-center py-4 text-sm">
            © {{ date('Y') }} House of Plastics. Do not reply to this email.
        </div>
    </div>

</body>

</html>
