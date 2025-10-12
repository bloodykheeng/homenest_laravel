<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Homenest</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md overflow-hidden mt-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-500 to-teal-600 text-white text-center py-6">
            <h1 class="text-2xl font-bold">🏡 Homenest</h1>
            <p class="text-sm mt-1">Your Home, Our Priority</p>
        </div>

        <!-- Main Content -->
        <div class="p-6">
            <h2 class="text-xl font-semibold text-center mb-4">Welcome to Homenest, {{ $user->name }}!</h2>
            <p class="mb-4 text-gray-700">
                We are thrilled to have you join our community. Your account has been successfully created, and you now
                have access to all the amazing features Homenest has to offer.
            </p>

            <!-- Account Details -->
            <div class="bg-gray-50 border-l-4 border-green-500 rounded p-4 mb-4">
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
                @if($plainPassword)
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Temporary Password:</span>
                    <span class="font-mono text-gray-600 bg-yellow-100 px-2 py-1 rounded">{{ $plainPassword }}</span>
                </div>
                @endif
                <div class="flex justify-between py-1">
                    <span class="font-semibold text-gray-700">Account Status:</span>
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded">
                        {{ $user->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            @if($plainPassword)
            <!-- Security Warning -->
            <div class="bg-yellow-100 border-l-4 border-yellow-400 p-4 mb-4 text-yellow-800">
                <strong>🛡️ Important Security Notice:</strong> Please change your password immediately after your first
                login. Do not share your credentials with anyone.
            </div>
            @endif

            <!-- Features -->
            <div class="mb-4">
                <h3 class="font-semibold mb-2 text-green-700">🎯 What You Can Do with Homenest:</h3>
                <ul class="list-disc list-inside space-y-1 text-gray-700">
                    <li>Browse our extensive collection of household items</li>
                    <li>Place and track your orders easily</li>
                    <li>Manage your account and preferences</li>
                    <li>Access exclusive deals and promotions</li>
                    <li>Enjoy fast and reliable delivery</li>
                    <li>Get personalized recommendations</li>
                </ul>
            </div>

            <p class="text-gray-700 mt-4">
                If you have any questions or need assistance, please don't hesitate to contact our support team.
                We're here to help!
            </p>

            <p class="text-gray-700 mt-4 font-semibold">
                Thank you for choosing Homenest. Happy shopping!
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
