<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $notification->title ?? 'Notification' }} - HomeNest</title>
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

        .notification-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .email-body {
            padding: 40px 30px;
        }

        .notification-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .notification-description {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .notification-meta {
            background-color: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 6px;
        }

        .notification-meta p {
            font-size: 14px;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .notification-meta p:last-child {
            margin-bottom: 0;
        }

        .notification-meta strong {
            color: #1a202c;
            font-weight: 600;
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

        .highlight-box {
            background-color: #fef5e7;
            border-left: 4px solid #f39c12;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 6px;
        }

        .highlight-box p {
            font-size: 14px;
            color: #7d5a0b;
            margin: 0;
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

            .notification-title {
                font-size: 20px;
            }

            .notification-description {
                font-size: 15px;
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
            <h1>🏠 HomeNest</h1>
            <p class="brand">Your Home Essentials Marketplace</p>
            @if (isset($notification) && $notification->type)
                <div class="notification-badge">
                    {{ $notification->type }} Notification
                </div>
            @endif
        </div>

        <!-- Body -->
        <div class="email-body">
            @if (isset($notification))
                <h2 class="notification-title">{{ $notification->title }}</h2>

                @if ($notification->description)
                    <div class="notification-description">
                        {!! nl2br(e($notification->description)) !!}
                    </div>
                @endif

                <div class="divider"></div>

                <div class="notification-meta">
                    @if ($notification->type)
                        <p><strong>Type:</strong> {{ ucfirst($notification->type) }}</p>
                    @endif

                    @if ($notification->target_audience)
                        <p><strong>Audience:</strong> {{ $notification->target_audience }}</p>
                    @endif

                    @if ($notification->start_date)
                        <p><strong>Valid From:</strong> {{ $notification->start_date->format('F j, Y, g:i a') }}</p>
                    @endif

                    @if ($notification->end_date)
                        <p><strong>Valid Until:</strong> {{ $notification->end_date->format('F j, Y, g:i a') }}</p>
                    @endif
                </div>

                @if ($notification->link)
                    <div style="text-align: center;">
                        <a href="{{ $notification->link }}" class="cta-button">
                            View Details →
                        </a>
                    </div>
                @endif

                @if ($notification->type === 'Promotional')
                    <div class="highlight-box">
                        <p>⚡ <strong>Limited Time Offer!</strong> Don't miss out on this amazing deal. Shop now and save
                            big on quality home essentials!</p>
                    </div>
                @endif

                @if ($notification->type === 'Order')
                    <div class="highlight-box">
                        <p>📦 <strong>Order Update:</strong> Track your order status and stay informed about your
                            delivery.</p>
                    </div>
                @endif
            @else
                <h2 class="notification-title">You have a new notification</h2>
                <p class="notification-description">Check your HomeNest account for more details.</p>
            @endif

            <div class="divider"></div>

            <p style="font-size: 14px; color: #718096; text-align: center;">
                Thank you for being a valued member of the HomeNest community!
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
                This email was sent to you because you are a registered user of HomeNest.<br>
                If you no longer wish to receive these emails, you can
                <a href="#" style="color: #667eea; text-decoration: none;">unsubscribe here</a>.
            </p>

            <p style="font-size: 12px; color: #a0aec0; margin-top: 15px;">
                © {{ date('Y') }} HomeNest. All rights reserved.<br>
                Kampala, Uganda
            </p>
        </div>
    </div>
</body>

</html>
