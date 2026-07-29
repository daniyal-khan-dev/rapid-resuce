<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Contacting Us</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #c0392b, #e74c3c); padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .header p { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .summary-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .summary-box .summary-title { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 14px; }
        .summary-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
        .summary-row:last-child { margin-bottom: 0; }
        .summary-label { color: #6b7280; font-weight: 600; min-width: 70px; flex-shrink: 0; }
        .summary-value { color: #1f2937; }
        .message-block { background: #fef2f2; border-left: 4px solid #e74c3c; border-radius: 6px; padding: 14px 18px; margin-top: 10px; }
        .message-block .label { font-size: 11px; font-weight: 700; color: #c0392b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .message-block p { color: #1f2937; font-size: 14px; line-height: 1.6; margin: 0; }
        .note { background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 6px; padding: 12px 16px; margin: 20px 0 0; }
        .note p { color: #166534; font-size: 13px; margin: 0; line-height: 1.5; }
        .cta { text-align: center; margin: 24px 0 8px; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #c0392b, #e74c3c); color: #fff; text-decoration: none; padding: 13px 30px; border-radius: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.02em; }
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
            <p>Thank you for reaching out to us. We have received your message and our support team will get back to you as soon as possible — usually within 24 hours.</p>

            <div class="summary-box">
                <div class="summary-title">Your Submission Summary</div>
                <div class="summary-row">
                    <span class="summary-label">Name</span>
                    <span class="summary-value">{{ $contactMessage->name }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Email</span>
                    <span class="summary-value">{{ $contactMessage->email }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Phone</span>
                    <span class="summary-value">{{ $contactMessage->phone }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Subject</span>
                    <span class="summary-value">{{ $contactMessage->subject }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Submitted</span>
                    <span class="summary-value">{{ $contactMessage->created_at->format('d M Y, h:i A') }}</span>
                </div>

                <div class="message-block">
                    <div class="label">Your Message</div>
                    <p>{{ $contactMessage->message }}</p>
                </div>
            </div>

            <div class="note">
                <p>📩 Our team will reply directly to this email address. There is no need to contact us again unless your situation is urgent — if so, please call our emergency hotline directly.</p>
            </div>

            <div class="cta">
                <a href="{{ url('/') }}">Visit Rapid Rescue →</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Rapid Rescue. All rights reserved. · This is an automated confirmation, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
