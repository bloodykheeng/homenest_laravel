<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Profile Updated - {{ $system_name ?? 'HomeNest' }}</title>
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
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
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

        .success-icon {
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

        .success-box {
            background-color: #f0fdf4;
            border-left: 4px solid #48bb78;
            padding: 25px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .success-box h3 {
            font-size: 18px;
            color: #1a202c;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .success-box h3 .icon {
            margin-right: 10px;
        }

        .update-list {
            list-style: none;
            padding: 0;
        }

        .update-list li {
            padding: 8px 0;
            font-size: 14px;
            color: #2d3748;
            display: flex;
            align-items: center;
        }

        .update-list li:before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            font-size: 18px;
            margin-right: 10px;
        }

        .password-alert {
            background-color: #fffbeb;
            border: 2px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .password-alert h4 {
            font-size: 16px;
            color: #92400e;
            margin-bottom: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .password-alert h4 .icon {
            margin-right: 8px;
            font-size: 20px;
        }

        .password-alert p {
            font-size: 14px;
            color: #78350f;
            margin: 5px 0;
            line-height: 1.6;
        }

        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .info-box p {
            font-size: 14px;
            color: #1e40af;
            margin: 5px 0;
        }

        .cta-section {
            text-align: center;
            margin: 30px 0;
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
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        .security-tips {
            background-color: #fef2f2;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }

        .security-tips h4 {
            font-size: 16px;
            color: #991b1b;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .security-tips ul {
            list-style: none;
            padding: 0;
        }

        .security-tips ul li {
            padding: 6px 0;
            font-size: 14px;
            color: #7f1d1d;
            padding-left: 20px;
            position: relative;
        }

        .security-tips ul li:before {
            content: "•";
            color: #dc2626;
            font-weight: bold;
            position: absolute;
            left: 5px;
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
            <div class="success-icon">✅</div>
            <h1>Profile Updated Successfully</h1>
            <p class="brand">{{ $system_name ?? 'HomeNest' }}</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2 class="greeting">Hello {{ $user->name }}! 👋</h2>

            <p class="intro-text">
                Great news! Your profile has been successfully updated. This email confirms the changes you made to your
                account.
            </p>

            <div class="success-box">
                <h3><span class="icon">🎉</span> Update Successful</h3>
                <ul class="update-list">
                    <li>Profile information has been updated</li>
                    @if ($password_changed)
                        <li>Password has been changed</li>
                    @endif
                    <li>Changes are now active and saved</li>
                    <li>Updated on: {{ now()->format('F j, Y, g:i a') }}</li>
                </ul>
            </div>

            @if ($password_changed)
                <div class="password-alert">
                    <h4><span class="icon">🔐</span> Password Changed</h4>
                    <p>
                        Your account password has been successfully updated. You'll need to use your new password
                        the next time you log in.
                    </p>
                    <p>
                        <strong>If you did not make this change,</strong> please contact our support team immediately
                        to secure your account.
                    </p>
                </div>
            @endif

            <div class="info-box">
                <p>
                    <strong>ℹ️ Note:</strong> These changes were made by you from your account settings.
                    If you did not authorize these changes, please secure your account immediately.
                </p>
            </div>

            <div class="cta-section">
                <a href="{{ config('app.url') }}/login" class="cta-button">
                    Access Your Account →
                </a>
            </div>

            <div class="divider"></div>

            <div class="security-tips">
                <h4>🛡️ Account Security Tips</h4>
                <ul>
                    <li>Use a strong, unique password for your account</li>
                    <li>Never share your password with anyone</li>
                    <li>Enable two-factor authentication if available</li>
                    <li>Regularly review your account activity</li>
                    <li>Log out from shared or public devices</li>
                </ul>
            </div>

            <p style="font-size: 14px; color: #718096; text-align: center; margin-top: 25px;">
                Thank you for keeping your account information up to date.
                If you have any questions, our support team is always here to help!
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>{{ $system_name ?? 'HomeNest' }}</strong></p>
            <p>Your trusted partner for quality service</p>

            <div class="social-links">
                <a href="#">Help Center</a> |
                <a href="#">Contact Support</a> |
                <a href="#">Account Settings</a>
            </div>

            <div class="divider" style="margin: 15px auto; max-width: 80%;"></div>

            <p style="font-size: 12px; color: #a0aec0;">
                This email was sent to {{ $user->email }} regarding your account update.
            </p>

            <p style="font-size: 12px; color: #a0aec0; margin-top: 15px;">
                © {{ date('Y') }} {{ $system_name ?? 'HomeNest' }}. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>
