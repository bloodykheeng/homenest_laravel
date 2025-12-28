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
            padding: 50px 30px;
            text-align: center;
            color: #ffffff;
        }

        .email-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .email-header .brand {
            font-size: 18px;
            opacity: 0.95;
            font-weight: 500;
            margin-top: 10px;
        }

        .welcome-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .email-body {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 26px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            text-align: center;
        }

        .intro-text {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
            border: 2px solid #e2e8f0;
        }

        .welcome-box h3 {
            font-size: 20px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .welcome-box p {
            font-size: 15px;
            color: #4a5568;
            line-height: 1.7;
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

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            margin: 25px 0;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 35px 0;
        }

        .feature-item {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .feature-item .icon {
            font-size: 40px;
            margin-bottom: 12px;
        }

        .feature-item h4 {
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .feature-item p {
            font-size: 13px;
            color: #718096;
            line-height: 1.5;
        }

        .benefits-section {
            background-color: #fef5e7;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
        }

        .benefits-section h3 {
            font-size: 20px;
            color: #7d5a0b;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
        }

        .benefits-list li {
            padding: 10px 0;
            font-size: 15px;
            color: #78350f;
            display: flex;
            align-items: center;
        }

        .benefits-list li:before {
            content: "✓";
            color: #f59e0b;
            font-weight: bold;
            font-size: 20px;
            margin-right: 12px;
            background-color: #fffbeb;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            margin: 0 10px;
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
            margin: 30px 0;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }

            .email-header {
                padding: 40px 20px;
            }

            .email-header h1 {
                font-size: 26px;
            }

            .email-body {
                padding: 30px 20px;
            }

            .greeting {
                font-size: 22px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .cta-button {
                padding: 14px 32px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="welcome-icon">🏠🎉</div>
            <h1>Welcome to HomeNest!</h1>
            <p class="brand">Your Journey to a Beautiful Home Starts Here</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <h2 class="greeting">Hello {{ $user->name }}! 👋</h2>

            <p class="intro-text">
                We're thrilled to have you join the HomeNest family! Your account has been successfully created,
                and you now have access to thousands of quality home essentials at your fingertips.
            </p>

            <div class="welcome-box">
                <h3>🎁 Your Account is Ready!</h3>
                <p>
                    Start exploring our curated collection of home essentials, from furniture to décor,
                    and transform your house into the home of your dreams. Enjoy exclusive deals,
                    fast delivery, and exceptional customer service.
                </p>
            </div>

            <div class="credentials-box">
                <h3>🔐 Your Login Credentials</h3>

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

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/login" class="cta-button">
                    Start Shopping Now →
                </a>
            </div>

            <div class="divider"></div>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="icon">🛍️</div>
                    <h4>Wide Selection</h4>
                    <p>Browse thousands of quality products for every room</p>
                </div>

                <div class="feature-item">
                    <div class="icon">🚚</div>
                    <h4>Fast Delivery</h4>
                    <p>Get your items delivered quickly and safely</p>
                </div>

                <div class="feature-item">
                    <div class="icon">💳</div>
                    <h4>Secure Payment</h4>
                    <p>Shop with confidence using encrypted transactions</p>
                </div>

                <div class="feature-item">
                    <div class="icon">🏷️</div>
                    <h4>Best Prices</h4>
                    <p>Enjoy competitive prices and exclusive discounts</p>
                </div>
            </div>

            <div class="benefits-section">
                <h3>✨ What You Get as a HomeNest Member</h3>
                <ul class="benefits-list">
                    <li>Exclusive access to new arrivals and seasonal collections</li>
                    <li>Special discounts and promotional offers</li>
                    <li>Order tracking and delivery updates</li>
                    <li>Wishlist to save your favorite items</li>
                    <li>24/7 customer support for all your needs</li>
                    <li>Easy returns and refund policy</li>
                </ul>
            </div>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #718096; text-align: center;">
                Need help getting started? Our friendly support team is here to assist you 24/7.
                Don't hesitate to reach out if you have any questions!
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p><strong>HomeNest</strong> - Making Houses Feel Like Home</p>
            <p>Quality products delivered with care</p>

            <div class="social-links">
                <a href="#">Facebook</a> |
                <a href="#">Instagram</a> |
                <a href="#">Twitter</a> |
                <a href="#">Help Center</a>
            </div>

            <div class="divider" style="margin: 15px auto; max-width: 80%;"></div>

            <p style="font-size: 12px; color: #a0aec0;">
                This email was sent to {{ $user->email }} because you created an account on HomeNest.
            </p>

            <p style="font-size: 12px; color: #a0aec0; margin-top: 15px;">
                © {{ date('Y') }} HomeNest. All rights reserved.<br>
                Kampala, Uganda
            </p>
        </div>
    </div>
</body>

</html>
