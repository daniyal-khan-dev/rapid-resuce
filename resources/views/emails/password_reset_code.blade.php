<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { max-width: 520px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #c0392b, #e74c3c); padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .header p { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .code-box { background: #fef2f2; border: 2px dashed #e74c3c; border-radius: 10px; text-align: center; padding: 24px 16px; margin: 24px 0; }
        .code-box span { font-size: 36px; font-weight: 800; letter-spacing: 10px; color: #c0392b; font-family: 'Courier New', monospace; }
        .note { background: #fff7ed; border-left: 4px solid #f97316; border-radius: 6px; padding: 12px 16px; margin: 20px 0; }
        .note p { color: #92400e; font-size: 13px; margin: 0; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>🚑 Rapid Rescue</h1>
            <p>Ambulance System</p>
        </div>
        <div class="body">
            <p>Hello, <strong>{{ $firstName }}</strong>!</p>
            <p>We received a request to reset your <strong>Rapid Rescue</strong> account password. Use the code below to continue:</p>
            <div class="code-box">
                <span>{{ $code }}</span>
            </div>
            <div class="note">
                <p>⏱ This code is valid for <strong>1 minute</strong>. Do not share it with anyone.</p>
            </div>
            <p>If you did not request a password reset, you can safely ignore this email. Your password will not change.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Rapid Rescue. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
