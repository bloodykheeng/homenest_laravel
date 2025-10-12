<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account Notification - Homenest</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden mt-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-600 text-white text-center py-6">
            <h1 class="text-2xl font-bold">🏡 Homenest Admin</h1>
            <p class="text-sm mt-1">User Management Notification</p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <h2 class="text-xl font-semibold text-center mb-4">Hello, {{ $admin->name }}!</h2>
            <p class="mb-4 text-gray-700">
                This is an automated notification to inform you that a user account has been
                <span class="font-bold text-purple-600">{{ $action }}</span> in the Homenest system.
            </p>

            <!-- Action Badge -->
            <div class="text-center mb-4">
                @if($action === 'created')
                    <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full font-semibold">
                        ✅ New User Created
                    </span>
                @elseif($action === 'updated')
                    <span class="inline-block px-4 py-2 bg-blue-100 text-blue-800 rounded-full font-semibold">
                        ✏️ User Updated
                    </span>
                @elseif($action === 'deleted')
                    <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full font-semibold">
                        🗑️ User Deleted
                    </span>
                @else
                    <span class="inline-block px-4 py-2 bg-gray-100 text-gray-800 rounded-full font-semibold">
                        📝 User {{ ucfirst($action) }}
                    </span>
                @endif
            </div>

            <!-- User Details -->
            <div class="bg-gray-50 border-l-4 border-purple-500 rounded p-4 mb-4">
                <h3 class="font-semibold text-gray-800 mb-2">User Details:</h3>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">User ID:</span>
                    <span class="font-mono text-gray-600">{{ $user->id }}</span>
                </div>
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
                    <span class="font-semibold text-gray-700">Role:</span>
                    <span class="font-mono text-gray-600">
                        @if($user->roles->isNotEmpty())
                            {{ $user->roles->pluck('name')->implode(', ') }}
                        @else
                            No role assigned
                        @endif
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Account Status:</span>
                    <span class="px-2 py-1 {{ $user->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded">
                        {{ $user->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if($action === 'created')
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Created At:</span>
                    <span class="font-mono text-gray-600">{{ $user->created_at->format('M d, Y h:i A') }}</span>
                </div>
                @elseif($action === 'updated')
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Last Updated:</span>
                    <span class="font-mono text-gray-600">{{ $user->updated_at->format('M d, Y h:i A') }}</span>
                </div>
                @endif
            </div>

            <!-- Admin Action Notice -->
            <div class="bg-purple-50 border-l-4 border-purple-400 p-4 mb-4 text-purple-800">
                <strong>👨‍💼 Admin Notice:</strong> This notification is sent to all System Administrators to keep you
                informed about user account changes. No action is required unless you need to review this change.
            </div>

            <!-- Action Items -->
            @if($action === 'created')
            <div class="mb-4">
                <h3 class="font-semibold mb-2 text-purple-700">📌 Suggested Actions:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Review the new user's information</li>
                    <li>Verify the assigned role is appropriate</li>
                    <li>Monitor initial activity if needed</li>
                    <li>Ensure account permissions are correct</li>
                </ul>
            </div>
            @elseif($action === 'updated')
            <div class="mb-4">
                <h3 class="font-semibold mb-2 text-purple-700">📌 Suggested Actions:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Review the updated information</li>
                    <li>Verify changes are authorized</li>
                    <li>Check if role changes were intentional</li>
                    <li>Monitor for any unusual activity</li>
                </ul>
            </div>
            @endif

            <p class="text-gray-700 mt-4 text-sm">
                This is an automated administrative notification from the Homenest system. For more details, please
                log in to the admin dashboard.
            </p>
        </div>

        <!-- Footer -->
        <div class="bg-gray-800 text-white text-center py-4 text-sm">
            <p>&copy; {{ date('Y') }} Homenest Admin Panel. All rights reserved.</p>
            <p class="mt-1 text-xs text-gray-400">This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>

</body>

</html>
