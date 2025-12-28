<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Account Updated - HomeNest</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
        }

        .email-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .email-header .brand {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 500;
            margin-top: 8px;
        }

        .update-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
        }

        .intro-text {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .update-summary {
            background-color: #f7fafc;
            border-left: 4px solid #48bb78;
            padding: 25px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .update-summary h3 {
            font-size: 18px;
            color: #1a202c;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .update-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
        }

        .update-item .icon {
            font-size: 20px;
            margin-right: 12px;
        }

        .update-item .text {
            font-size: 14px;
            color: #2d3748;
        }

        .password-changed-alert {
            background-color: #fef5e7;
            border: 2px solid #f39c12;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
        }

        .password-changed-alert .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .password-changed-alert h4 {
            font-size: 18px;
            color: #7d5a0b;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .password-changed-alert p {
            font-size: 14px;
            color: #7d5a0b;
            margin: 5px 0;
        }

        .security-notice {
            background-color: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .security-notice h4 {
            font-size: 16px;
            color: #9b2c2c;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .security-notice p {
            font-size: 14px;
            color: #742a2a;
            margin: 8px 0;
            line-height: 1.6;
        }

        .security-notice strong {
            color: #9b2c2c;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        .account-details {
            background-color: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .account-details h4 {
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #cbd5e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
        }

        .detail-value {
            color: #2d3748;
            font-size: 14px;
        }

        .email-footer {
            background-color: #f7fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .email-footer p {
            font-size: 14px;
            color: #718096;
            margin-bottom: 10px;
        }

        .email-footer .social-links {
            margin: 20px 0;
        }

        .email-footer .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .email-footer .social-links a:hover {
            color: #764ba2;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #cbd5e0, transparent);
            margin: 25px 0;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .email-header {
                padding: 30px 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .greeting {
                font-size: 20px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .cta-button {
                padding: 12px 24px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="update-icon">🔄</div>
            <h1>Account Updated</h1>
            <p class="brand">HomeNest - Your Home Essentials Marketplace</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2 class="greeting">Hello {{ $user->name }}! 👋</h2>

            <p class="intro-text">
                This is to inform you that your HomeNest account has been successfully updated.
                Below are the details of the changes made to your account.
            </p>

            @if ($password_changed)
                <div class="password-changed-alert">
                    <div class="icon">🔐</div>
                    <h4>Password Changed</h4>
                    <p>Your account password has been updated successfully.</p>
                    <p>If you did not make this change, please contact our support team immediately.</p>
                </div>
            @endif

            <div class="update-summary">
                <h3>✅ Update Confirmation</h3>

                <div class="update-item">
                    <span class="icon">👤</span>
                    <span class="text">Account information has been updated</span>
                </div>

                @if ($password_changed)
                    <div class="update-item">
                        <span class="icon">🔒</span>
                        <span class="text">Password has been changed</span>
                    </div>
                @endif

                <div class="update-item">
                    <span class="icon">📅</span>
                    <span class="text">Updated on: {{ now()->format('F j, Y, g:i a') }}</span>
                </div>
            </div>

            <div class="account-details">
                <h4>📋 Current Account Information</h4>

                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $user->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $user->email }}</span>
                </div>

                @if ($user->username)
                    <div class="detail-row">
                        <span class="detail-label">Username:</span>
                        <span class="detail-value">{{ $user->username }}</span>
                    </div>
                @endif

                @if ($user->phone)
                    <div class="detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">{{ $user->phone }}</span>
                    </div>
                @endif

                @if ($user->country)
                    <div class="detail-row">
                        <span class="detail-label">Country:</span>
                        <span class="detail-value">{{ $user->country }}</span>
                    </div>
                @endif
            </div>

            <div class="security-notice">
                <h4>🛡️ Security Notice</h4>
                <p>
                    If you did <strong>not</strong> make these changes or believe your account has been compromised,
                    please take the following actions immediately:
                </p>
                <p>• Contact our support team</p>
                <p>• Change your password</p>
                <p>• Review your account activity</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/login" class="cta-button">
                    Login to Your Account →
                </a>
            </div>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #718096; text-align: center;">
                If you have any questions or concerns about this update, please don't hesitate to contact our support
                team.
                We're here to help!
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>HomeNest</strong> - Your One-Stop Shop for Home Essentials</p>
            <p>Quality products delivered to your doorstep</p>

            <div class="social-links">
                <a href="#">Facebook</a> |
                <a href="#">Twitter</a> |
                <a href="#">Instagram</a> |
                <a href="#">Contact Support</a>
            </div>

            <div class="divider" style="margin: 15px auto; max-width: 80%;"></div>

            <p style="font-size: 12px; color: #a0aec0;">
                This email was sent to {{ $user->email }} regarding your HomeNest account.
            </p>

            <p style="font-size: 12px; color: #a0aec0; margin-top: 15px;">
                © {{ date('Y') }} HomeNest. All rights reserved.<br>
                Kampala, Uganda
            </p>
        </div>
    </div>
</body>

</html>
