<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Updated - Homenest</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden mt-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-center py-6">
            <h1 class="text-2xl font-bold">🏡 Homenest</h1>
            <p class="text-sm mt-1">Account Update Notification</p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <h2 class="text-xl font-semibold text-center mb-4">Hello, {{ $user->name }}!</h2>
            <p class="mb-4 text-gray-700">
                This email is to inform you that your Homenest account has been recently updated.
            </p>

            <!-- Account Details -->
            <div class="bg-gray-50 border-l-4 border-blue-500 rounded p-4 mb-4">
                <h3 class="font-semibold text-gray-800 mb-2">Updated Account Information:</h3>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Full Name:</span>
                    <span class="font-mono text-gray-600">{{ $user->name }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Email:</span>
                    <span class="font-mono text-gray-600">{{ $user->email }}</span>
                </div>
                @if($user->username)
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Username:</span>
                    <span class="font-mono text-gray-600">{{ $user->username }}</span>
                </div>
                @endif
                @if($user->phone)
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Phone:</span>
                    <span class="font-mono text-gray-600">{{ $user->phone }}</span>
                </div>
                @endif
                @if($user->gender)
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Gender:</span>
                    <span class="font-mono text-gray-600">{{ ucfirst($user->gender) }}</span>
                </div>
                @endif
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Account Status:</span>
                    <span class="px-2 py-1 {{ $user->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded">
                        {{ $user->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Last Updated:</span>
                    <span class="font-mono text-gray-600">{{ $user->updated_at->format('M d, Y h:i A') }}</span>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4 text-blue-800">
                <strong>🔐 Security Notice:</strong> If you did not make these changes or if you notice any
                unauthorized activity, please contact our support team immediately to secure your account.
            </div>

            <!-- Action Items -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2 text-blue-700">📌 What You Should Do:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Review the changes to ensure they are accurate</li>
                    <li>If you didn't make these changes, reset your password immediately</li>
                    <li>Contact support if you have any concerns</li>
                    <li>Keep your account information secure</li>
                </ul>
            </div>

            <p class="text-gray-700 mt-4">
                If you have any questions or need assistance, please don't hesitate to contact our support team.
                We're here to help keep your account safe and secure.
            </p>

            <p class="text-gray-700 mt-4 font-semibold">
                Thank you for being a valued member of Homenest!
            </p>
        </div>

        <!-- Footer -->
        <div class="bg-gray-800 text-white text-center py-4 text-sm">
            <p>&copy; {{ date('Y') }} Homenest. All rights reserved.</p>
            <p class="mt-1 text-xs text-gray-400">This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>

</body>

</html>
