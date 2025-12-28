<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome to HomeNest</title>
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

        .welcome-icon {
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

        .credentials-box {
            background-color: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 25px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .credentials-box h3 {
            font-size: 18px;
            color: #1a202c;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        .credential-label {
            font-weight: 600;
            color: #2d3748;
            font-size: 14px;
        }

        .credential-value {
            color: #4a5568;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            background-color: #edf2f7;
            padding: 4px 12px;
            border-radius: 4px;
        }

        .password-highlight {
            background-color: #fef5e7;
            border: 2px solid #f39c12;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
        }

        .password-highlight p {
            font-size: 14px;
            color: #7d5a0b;
            margin: 0;
        }

        .password-highlight strong {
            color: #c87d0e;
            font-size: 16px;
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

        .security-notice {
            background-color: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .security-notice p {
            font-size: 14px;
            color: #742a2a;
            margin: 5px 0;
        }

        .security-notice strong {
            color: #9b2c2c;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 25px 0;
        }

        .feature-item {
            background-color: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .feature-item .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .feature-item h4 {
            font-size: 14px;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .feature-item p {
            font-size: 12px;
            color: #718096;
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

            .features-grid {
                grid-template-columns: 1fr;
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
            <div class="welcome-icon">🏠</div>
            <h1>Welcome to HomeNest!</h1>
            <p class="brand">Your Home Essentials Marketplace</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2 class="greeting">Hello {{ $user->name }}! 👋</h2>

            <p class="intro-text">
                Welcome to HomeNest! We're excited to have you join our community. Your account has been successfully
                created,
                and you're now ready to explore our wide range of quality home essentials.
            </p>

            <div class="credentials-box">
                <h3>🔐 Your Account Credentials</h3>

                <div class="credential-item">
                    <span class="credential-label">Email:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>

                @if ($user->username)
                    <div class="credential-item">
                        <span class="credential-label">Username:</span>
                        <span class="credential-value">{{ $user->username }}</span>
                    </div>
                @endif

                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value">{{ $plainPassword }}</span>
                </div>
            </div>

            <div class="password-highlight">
                <p>
                    🔒 <strong>Important:</strong> Please change your password after your first login for security
                    purposes.
                </p>
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/login" class="cta-button">
                    Login to Your Account →
                </a>
            </div>

            <div class="divider"></div>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="icon">🛍️</div>
                    <h4>Shop Quality Products</h4>
                    <p>Browse thousands of home essentials</p>
                </div>

                <div class="feature-item">
                    <div class="icon">🚚</div>
                    <h4>Fast Delivery</h4>
                    <p>Quick and reliable shipping</p>
                </div>

                <div class="feature-item">
                    <div class="icon">💳</div>
                    <h4>Secure Payments</h4>
                    <p>Safe and encrypted transactions</p>
                </div>

                <div class="feature-item">
                    <div class="icon">⭐</div>
                    <h4>Great Deals</h4>
                    <p>Exclusive offers and discounts</p>
                </div>
            </div>

            <div class="security-notice">
                <p><strong>🛡️ Security Tips:</strong></p>
                <p>• Never share your password with anyone</p>
                <p>• Use a strong, unique password</p>
                <p>• Enable two-factor authentication if available</p>
                <p>• Log out from shared devices</p>
            </div>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #718096; text-align: center;">
                Need help getting started? Our support team is here to assist you 24/7.
                Feel free to reach out to us anytime!
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
                <a href="#">Contact Us</a>
            </div>

            <div class="divider" style="margin: 15px auto; max-width: 80%;"></div>

            <p style="font-size: 12px; color: #a0aec0;">
                This email was sent to {{ $user->email }} because an account was created for you on HomeNest.
            </p>

            <p style="font-size: 12px; color: #a0aec0; margin-top: 15px;">
                © {{ date('Y') }} HomeNest. All rights reserved.<br>
                Kampala, Uganda
            </p>
        </div>
    </div>
</body>

</html>
