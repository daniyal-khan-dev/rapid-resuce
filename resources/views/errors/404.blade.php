<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found | Rapid Rescue</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --rr-primary:       #B91C2C;
            --rr-primary-dark:  #8B1622;
            --rr-primary-soft:  #E84A5F;
            --rr-navy:          #0F1A2E;
            --rr-navy-light:    #1F2E4E;
            --rr-muted:         #8B93A6;
            --rr-grad-hero:     linear-gradient(120deg, #0F1A2E 0%, #1F2E4E 55%, #8B1622 100%);
        }

        html, body {
            height: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--rr-grad-hero);
            color: #fff;
            overflow-x: hidden;
        }

        /* ── Background decoration ── */
        .rr-404-bg {
            position: fixed;
            inset: 0;
            background: var(--rr-grad-hero);
            z-index: 0;
        }
        .rr-404-bg::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(185,28,44,.25) 0%, transparent 70%);
        }
        .rr-404-bg::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(31,46,78,.6) 0%, transparent 70%);
        }

        /* ── Wrapper ── */
        .rr-404-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            text-align: center;
        }

        /* ── Logo ── */
        .rr-404-logo {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            text-decoration: none;
            margin-bottom: 3rem;
        }
        .rr-404-logo img {
            width: 40px;
            filter: brightness(0) invert(1);
        }
        .rr-404-logo-text strong {
            display: block;
            font-size: 1.05rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: .02em;
        }
        .rr-404-logo-text small {
            font-size: 0.7rem;
            color: rgba(255,255,255,.55);
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* ── Illustration ── */
        .rr-404-icon {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 2rem;
        }
        .rr-404-icon-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid rgba(185,28,44,.35);
            animation: rr-pulse-ring 2.8s ease-out infinite;
        }
        .rr-404-icon-ring:nth-child(2) { animation-delay: .9s; }
        .rr-404-icon-ring:nth-child(3) { animation-delay: 1.8s; }
        @keyframes rr-pulse-ring {
            0%   { transform: scale(.75); opacity: .7; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        .rr-404-icon-inner {
            position: absolute;
            inset: 20px;
            border-radius: 50%;
            background: rgba(185,28,44,.12);
            border: 2px solid rgba(185,28,44,.4);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rr-404-icon-inner i {
            font-size: 2.6rem;
            color: var(--rr-primary-soft);
        }

        /* ── Heading ── */
        .rr-404-num {
            font-size: clamp(5rem, 18vw, 9rem);
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #fff 30%, rgba(232,74,95,.75) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -.03em;
            margin-bottom: .35rem;
        }
        .rr-404-title {
            font-size: clamp(1.3rem, 4vw, 1.9rem);
            font-weight: 700;
            color: #fff;
            margin-bottom: .9rem;
        }
        .rr-404-desc {
            font-size: clamp(.9rem, 2.5vw, 1.05rem);
            color: rgba(255,255,255,.6);
            max-width: 430px;
            line-height: 1.65;
            margin: 0 auto 2.5rem;
        }

        /* ── Divider ── */
        .rr-404-divider {
            width: 48px;
            height: 3px;
            border-radius: 2px;
            background: var(--rr-primary);
            margin: 0 auto 1.8rem;
        }

        /* ── Buttons ── */
        .rr-404-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .rr-btn-primary-404 {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.8rem;
            background: linear-gradient(135deg, #D72C42, #B91C2C);
            color: #fff;
            font-family: inherit;
            font-size: .95rem;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(185,28,44,.35);
            transition: transform .2s, box-shadow .2s;
        }
        .rr-btn-primary-404:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(185,28,44,.5);
            color: #fff;
        }
        .rr-btn-ghost-404 {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.8rem;
            background: rgba(255,255,255,.07);
            color: rgba(255,255,255,.85);
            font-family: inherit;
            font-size: .95rem;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 50px;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .2s;
        }
        .rr-btn-ghost-404:hover {
            background: rgba(255,255,255,.12);
            border-color: rgba(255,255,255,.28);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ── Footer note ── */
        .rr-404-footer {
            position: relative;
            z-index: 1;
            padding: 1.5rem;
            text-align: center;
            font-size: .78rem;
            color: rgba(255,255,255,.3);
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .rr-404-actions { flex-direction: column; align-items: center; }
            .rr-btn-primary-404, .rr-btn-ghost-404 { width: 100%; max-width: 260px; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="rr-404-bg"></div>

    <div class="rr-404-wrap">

        <a href="{{ route('home') }}" class="rr-404-logo">
            <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
            <div class="rr-404-logo-text">
                <strong>Rapid Rescue</strong>
                <small>Ambulance System</small>
            </div>
        </a>

        <div class="rr-404-icon" aria-hidden="true">
            <div class="rr-404-icon-ring"></div>
            <div class="rr-404-icon-ring"></div>
            <div class="rr-404-icon-ring"></div>
            <div class="rr-404-icon-inner">
                <i class="fa fa-map-location-dot"></i>
            </div>
        </div>

        <div class="rr-404-num">404</div>
        <h1 class="rr-404-title">Page Not Found</h1>
        <div class="rr-404-divider"></div>
        <p class="rr-404-desc">
            The page you're looking for doesn't exist or may have been moved.<br>
            Let us guide you back to safety.
        </p>

        <div class="rr-404-actions">
            <a href="{{ route('home') }}" class="rr-btn-primary-404">
                <i class="fa fa-house"></i>
                Back to Home
            </a>
            <button onclick="history.back()" class="rr-btn-ghost-404">
                <i class="fa fa-arrow-left"></i>
                Go Back
            </button>
        </div>

    </div>

    <footer class="rr-404-footer">
        &copy; {{ date('Y') }} Rapid Rescue. All rights reserved.
    </footer>
</body>
</html>
