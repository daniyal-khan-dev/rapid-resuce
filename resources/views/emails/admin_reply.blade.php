<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Reply</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #c0392b, #e74c3c); padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .header p { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .original-msg { background: #f3f4f6; border-left: 4px solid #9ca3af; border-radius: 6px; padding: 14px 18px; margin: 0 0 20px; }
        .original-msg .label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
        .original-msg p { color: #374151; font-size: 14px; margin: 0; line-height: 1.55; }
        .reply-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 20px 22px; margin: 20px 0; }
        .reply-box .label { font-size: 11px; font-weight: 700; color: #c0392b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; }
        .reply-box p { color: #1f2937; font-size: 15px; line-height: 1.6; margin: 0; }
        .cta { text-align: center; margin: 28px 0 8px; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #c0392b, #e74c3c); color: #fff; text-decoration: none; padding: 13px 30px; border-radius: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.02em; }
        .note { background: #fff7ed; border-left: 4px solid #f97316; border-radius: 6px; padding: 12px 16px; margin: 20px 0 0; }
        .note p { color: #92400e; font-size: 13px; margin: 0; }
        .guest-note { background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 6px; padding: 12px 16px; margin: 20px 0 0; }
        .guest-note p { color: #166534; font-size: 13px; margin: 0; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>🚑 Rapid Rescue</h1>
            <p>Ambulance System · Support Team</p>
        </div>
        <div class="body">
            <p>Hello, <strong>{{ $contactMessage->name }}</strong>!</p>
            <p>Our support team has replied to your message. Here's a summary:</p>

            <div class="original-msg">
                <div class="label">Your original message — {{ $contactMessage->subject }}</div>
                <p>{{ Str::limit($contactMessage->message, 200) }}</p>
            </div>

            <div class="reply-box">
                <div class="label">Support Reply · {{ $contactReply->created_at ? $contactReply->created_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</div>
                <p>{{ $contactReply->message }}</p>
            </div>

            @if($contactMessage->user_id)
                {{-- Registered user: show link to login and view conversation --}}
                <div class="cta">
                    <a href="{{ url('/login') }}">View Full Conversation →</a>
                </div>
                <div class="note">
                    <p>💬 You can reply directly by logging into your Rapid Rescue account and visiting <strong>Contact History</strong> in your profile.</p>
                </div>
            @else
                {{-- Guest: no account, just direct them to contact again if needed --}}
                <div class="guest-note">
                    <p>📩 If you have further questions or need additional help, please feel free to contact us again at <a href="{{ url('/') }}#contact" style="color:#15803d;font-weight:600;">rapidrescue.com</a>. We are happy to assist you.</p>
                </div>
            @endif
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Rapid Rescue. All rights reserved. · This is an automated notification, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
