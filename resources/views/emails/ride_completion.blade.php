<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Completed</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #15803d, #22c55e); padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .header p { color: rgba(255,255,255,.85); margin: 6px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .rreb-badge { display: inline-block; background: #f0fdf4; border: 1px solid #86efac; color: #15803d; font-weight: 700; font-size: 16px; padding: 6px 16px; border-radius: 8px; letter-spacing: 0.04em; margin-bottom: 20px; }
        .summary-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px 24px; margin: 20px 0; }
        .summary-box .summary-title { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 14px; }
        .summary-row { display: flex; gap: 12px; margin-bottom: 10px; font-size: 14px; }
        .summary-row:last-child { margin-bottom: 0; }
        .summary-label { color: #6b7280; font-weight: 600; min-width: 130px; flex-shrink: 0; }
        .summary-value { color: #1f2937; }
        .status-badge { display: inline-block; background: #dcfce7; border: 1px solid #86efac; color: #15803d; font-size: 12px; font-weight: 700; padding: 2px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em; }
        .note { background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 6px; padding: 12px 16px; margin: 20px 0 0; }
        .note p { color: #166534; font-size: 13px; margin: 0; line-height: 1.5; }
        .cta { text-align: center; margin: 24px 0 8px; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #15803d, #22c55e); color: #fff; text-decoration: none; padding: 13px 30px; border-radius: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.02em; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>✅ Rapid Rescue</h1>
            <p>Emergency Dispatch System · Ride Completed</p>
        </div>
        <div class="body">
            <p>Your emergency ride has been <strong>successfully completed</strong>. We hope you received the care you needed. Thank you for trusting Rapid Rescue.</p>

            <div style="text-align:center; margin-bottom:8px;">
                <span class="rreb-badge">{{ $emergencyRequest->rreb_id }}</span>
            </div>

            <div class="summary-box">
                <div class="summary-title">Ride Summary</div>

                <div class="summary-row">
                    <span class="summary-label">Trip / Request ID</span>
                    <span class="summary-value">{{ $emergencyRequest->rreb_id }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Final Status</span>
                    <span class="summary-value"><span class="status-badge">Completed</span></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Type</span>
                    <span class="summary-value">{{ $emergencyRequest->type === '1' ? 'Emergency' : 'Non-Emergency' }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Pickup Location</span>
                    <span class="summary-value">{{ $emergencyRequest->pickup_address }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Hospital</span>
                    <span class="summary-value">{{ $emergencyRequest->hospital_name }}</span>
                </div>
                @if($emergencyRequest->driver)
                <div class="summary-row">
                    <span class="summary-label">Driver</span>
                    <span class="summary-value">{{ $emergencyRequest->driver->name }}</span>
                </div>
                @endif
                @if($emergencyRequest->ambulance)
                <div class="summary-row">
                    <span class="summary-label">Ambulance</span>
                    <span class="summary-value">{{ $emergencyRequest->ambulance->vehicle_number }} ({{ $emergencyRequest->ambulance->type }})</span>
                </div>
                @endif
                @if($emergencyRequest->dispatched_at)
                <div class="summary-row">
                    <span class="summary-label">Dispatched At</span>
                    <span class="summary-value">{{ $emergencyRequest->dispatched_at->format('d M Y, h:i A') }}</span>
                </div>
                @endif
                <div class="summary-row">
                    <span class="summary-label">Completed At</span>
                    <span class="summary-value">{{ $emergencyRequest->completed_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>

            <div class="note">
                <p>💚 If you have any feedback about your experience, please don't hesitate to contact us. Your safety and comfort are our top priority.</p>
            </div>

            <div class="cta">
                <a href="{{ url('/') }}">Visit Rapid Rescue →</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Rapid Rescue. All rights reserved. · This is an automated notification, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
