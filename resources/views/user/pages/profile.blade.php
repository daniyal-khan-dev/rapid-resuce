@extends('user.layouts.user')
@section('title', 'Profile | Rapid Rescue')

@push('styles')
    <style>
        .rr-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 600px) {
            .rr-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        .rr-profile-banner {
            position: relative;
            height: 80px;
            background: var(--rr-grad-hero);
            border-radius: var(--rr-radius) var(--rr-radius) 0 0;
        }

        .rr-profile-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 50%, rgba(232, 74, 95, 0.30), transparent 50%), radial-gradient(circle at 80% 30%, rgba(31, 46, 78, 0.45), transparent 55%);
        }

        .rr-profile-banner-avatar {
            position: absolute;
            bottom: -44px;
            left: 32px;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid #fff;
            object-fit: cover;
            box-shadow: var(--rr-shadow-md);
            background: #f0f0f0;
        }

        .rr-profile-card-header {
            padding: 56px 32px 20px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            border-bottom: 1px solid var(--rr-border);
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .rr-profile-card-header h3 {
            margin: 0 0 2px;
            font-size: 1.3rem;
        }

        .rr-profile-card-header p {
            margin: 0;
            color: var(--rr-text-muted);
            font-size: 0.9rem;
        }

        .rr-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        @media (max-width: 680px) {
            .rr-info-grid {
                grid-template-columns: 1fr;
            }
        }

        .rr-info-cell {
            padding: 16px 0;
            border-bottom: 1px dashed var(--rr-border);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .rr-info-cell:nth-child(odd) {
            padding-right: 24px;
            border-right: 1px dashed var(--rr-border);
        }

        .rr-info-cell:nth-child(even) {
            padding-left: 24px;
        }

        .rr-info-cell:nth-last-child(-n+2) {
            border-bottom: none;
        }

        @media (max-width: 680px) {
            .rr-info-cell:nth-child(odd) {
                padding-right: 0;
                border-right: none;
            }

            .rr-info-cell:nth-child(even) {
                padding-left: 0;
            }

            .rr-info-cell:nth-last-child(1) {
                border-bottom: none;
            }

            .rr-info-cell:nth-last-child(2) {
                border-bottom: 1px dashed var(--rr-border);
            }
        }

        .rr-info-cell__label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--rr-text-light);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rr-info-cell__label i {
            color: var(--rr-primary);
            font-size: 0.75rem;
        }

        .rr-info-cell__value {
            font-size: 0.97rem;
            font-weight: 600;
            color: var(--rr-navy);
        }

        .rr-med-card {
            position: relative;
            border-radius: 20px;
            background: var(--rr-grad-hero);
            color: #fff;
            padding: 28px 28px 24px;
            overflow: hidden;
            box-shadow: 0 20px 48px rgba(var(--rr-primary-rgb), 0.30);
            margin-bottom: 28px;
        }

        .rr-med-card::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
        }

        .rr-med-card::after {
            content: '';
            position: absolute;
            bottom: -40px;
            left: -30px;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
        }

        .rr-med-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            position: relative;
            z-index: 2;
        }

        .rr-med-card__logo {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .rr-med-card__logo i {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.20);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .rr-med-card__logo span {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.80;
        }

        .rr-med-card__blood {
            text-align: right;
        }

        .rr-med-card__blood span {
            display: block;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            opacity: 0.65;
            margin-bottom: 2px;
        }

        .rr-med-card__blood strong {
            font-size: 1.9rem;
            font-weight: 900;
            line-height: 1;
            color: #fff;
        }

        .rr-med-card__name {
            position: relative;
            z-index: 2;
            margin-bottom: 20px;
        }

        .rr-med-card__name p {
            margin: 0 0 2px;
            font-size: 0.70rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            opacity: 0.60;
        }

        .rr-med-card__name h4 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
        }

        .rr-med-card__chips {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            position: relative;
            z-index: 2;
            margin-bottom: 20px;
        }

        .rr-med-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.20);
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.76rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        .rr-med-chip i {
            font-size: 0.72rem;
            opacity: 0.80;
        }

        .rr-med-chip--alert {
            background: rgba(255, 80, 80, 0.22);
            border-color: rgba(255, 80, 80, 0.35);
        }

        .rr-med-card__footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            padding-top: 16px;
            position: relative;
            z-index: 2;
        }

        .rr-med-card__contact p {
            margin: 0 0 2px;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            opacity: 0.55;
        }

        .rr-med-card__contact strong {
            font-size: 0.90rem;
            font-weight: 700;
            color: #fff;
            display: block;
        }

        .rr-med-card__contact small {
            font-size: 0.78rem;
            opacity: 0.65;
        }

        .rr-med-card__qr {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            opacity: 0.70;
        }

        /* Empty medical card */
        .rr-med-card--empty {
            background: transparent;
            border: 2px dashed var(--rr-border);
            box-shadow: none;
            text-align: center;
            padding: 48px 28px;
        }

        .rr-med-card--empty .rr-med-empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--rr-primary-50);
            color: var(--rr-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 16px;
        }

        .rr-med-card--empty h4 {
            color: var(--rr-navy);
            margin: 0 0 8px;
        }

        .rr-med-card--empty p {
            color: var(--rr-text-muted);
            margin: 0 0 20px;
            font-size: 0.9rem;
        }

        /* Medical details below card */
        .rr-med-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        @media (max-width: 600px) {
            .rr-med-details {
                grid-template-columns: 1fr;
            }
        }

        .rr-med-detail-block {
            background: var(--rr-bg-soft);
            border: 1px solid var(--rr-border);
            border-radius: 14px;
            padding: 18px 20px;
        }

        .rr-med-detail-block h6 {
            margin: 0 0 6px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--rr-text-light);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rr-med-detail-block h6 i {
            color: var(--rr-primary);
        }

        .rr-med-detail-block p {
            margin: 0;
            color: var(--rr-navy);
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Emergency contact block */
        .rr-emergency-block {
            background: var(--rr-bg-warm);
            border: 1px solid rgba(var(--rr-primary-rgb), 0.18);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .rr-emergency-block__icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--rr-grad-primary);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            box-shadow: var(--rr-shadow-primary);
        }

        .rr-emergency-block__info {
            flex: 1;
        }

        .rr-emergency-block__info p {
            margin: 0 0 1px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--rr-primary);
        }

        .rr-emergency-block__info strong {
            display: block;
            font-size: 1rem;
            font-weight: 800;
            color: var(--rr-navy);
        }

        .rr-emergency-block__info small {
            color: var(--rr-text-muted);
            font-size: 0.83rem;
        }

        /* Modal photo upload */
        .rr-photo-upload {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 18px;
            background: var(--rr-bg-soft);
            border: 1.5px dashed var(--rr-border);
            border-radius: 14px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .rr-photo-upload:hover {
            border-color: var(--rr-primary);
            background: var(--rr-bg-warm);
        }

        .rr-photo-upload__preview {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--rr-primary-100);
            flex-shrink: 0;
        }

        .rr-photo-upload__text strong {
            display: block;
            color: var(--rr-navy);
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .rr-photo-upload__text small {
            color: var(--rr-text-light);
            font-size: 0.80rem;
        }

        .rr-photo-upload input[type="file"] {
            display: none;
        }

        /* Section divider in modal */
        .rr-modal-section {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 14px;
        }

        .rr-modal-section span {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--rr-text-light);
            white-space: nowrap;
        }

        .rr-modal-section::before,
        .rr-modal-section::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--rr-border);
        }

        /* Medical modal note */
        .rr-med-note {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--rr-bg-warm);
            border: 1px solid rgba(var(--rr-primary-rgb), 0.18);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            color: var(--rr-primary);
            font-size: 0.83rem;
            font-weight: 600;
        }

        .rr-med-note i {
            flex-shrink: 0;
        }

        @keyframes rrTypingBounce {

            0%,
            80%,
            100% {
                transform: translateY(0);
                opacity: .4;
            }

            40% {
                transform: translateY(-4px);
                opacity: 1;
            }
        }
    </style>
@endpush

@section('content')
    <div class="rr-breadcrumb">
        <div class="rr-container">
            <h1 id="breadcrumbTitle">Profile</h1>
            <div class="rr-breadcrumb__path">
                <a href="{{ route('home') }}">Home</a>
                <i class="fa fa-chevron-right"></i>
                <span id="breadcrumbText">Profile</span>
            </div>
        </div>
    </div>

    <div class="rr-profile">
        <div class="container">
            <div class="rr-profile__grid">
                {{-- Sidebar nav --}}
                <aside class="rr-profile__nav">
                    <div class="nav flex-column" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill" href="#v-pills-profile"
                            role="tab" data-rr-hash="#profile" data-rr-title="Profile | Rapid Rescue"
                            data-rr-label="Profile">
                            <i class="fa fa-user"></i> Profile
                        </a>

                        <a class="nav-link" id="v-pills-messages-tab" data-bs-toggle="pill" href="#v-pills-medprofile"
                            role="tab" data-rr-hash="#medical-card" data-rr-title="Medical Card | Rapid Rescue"
                            data-rr-label="Medical Card">
                            <i class="fa fa-id-card-clip"></i> Medical Card
                        </a>

                        <a class="nav-link" id="v-pills-settings-tab" data-bs-toggle="pill" href="#v-pills-instructions"
                            role="tab" data-rr-hash="#first-aid" data-rr-title="First-Aid Guide | Rapid Rescue"
                            data-rr-label="First-Aid Guide">
                            <i class="fa fa-kit-medical"></i> First-Aid Guide
                        </a>

                        <a class="nav-link" id="v-pills-bookings-tab" data-bs-toggle="pill" href="#v-pills-bookings"
                            role="tab" data-rr-hash="#my-bookings" data-rr-title="My Bookings | Rapid Rescue"
                            data-rr-label="My Bookings">
                            <i class="fa fa-ambulance"></i> My Bookings
                        </a>

                        <a class="nav-link" id="v-pills-contacts-tab" data-bs-toggle="pill" href="#v-pills-contacts"
                            role="tab" data-rr-hash="#contact-history" data-rr-title="Contact History | Rapid Rescue"
                            data-rr-label="Contact History">
                            <i class="fa fa-envelope-open-text"></i> Contact History
                        </a>

                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="nav-link w-100 text-start"
                                style="color:var(--rr-primary);background:none;border:none;cursor:pointer;">
                                <i class="fa fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </aside>

                {{-- Tab content --}}
                <div class="tab-content" style="z-index: 999;" id="v-pills-tabContent">
                    {{-- PROFILE TAB --}}
                    <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                        <div class="rr-profile-card" style="padding:0;overflow:hidden;">
                            {{-- Banner + avatar --}}
                            <div class="rr-profile-banner">
                                <img class="rr-profile-banner-avatar"
                                    src="{{ asset('assets/user/img/users/' . ($userDetail->profile_picture ?? 'default.jpg')) }}"
                                    alt="Profile photo" id="profileAvatarDisplay">
                            </div>

                            {{-- Header below banner --}}
                            <div class="rr-profile-card-header">
                                <div>
                                    <h3>{{ $userDetail->first_name }} {{ $userDetail->last_name }}</h3>
                                    <p><i class="fa fa-circle" style="color:#22c55e;font-size:0.6rem;margin-right:4px;"></i>
                                        Active member &middot; Consumer No: {{ $userDetail->consumer_no }}
                                    </p>
                                </div>

                                <button class="rr-btn rr-btn--primary" data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="fa fa-pen"></i> Edit Profile
                                </button>
                            </div>

                            {{-- Info grid --}}
                            <div style="padding: 0 32px 32px;">
                                <div class="rr-info-grid">
                                    <div class="rr-info-cell">
                                        <span class="rr-info-cell__label"><i class="fa fa-envelope"></i> Email</span>
                                        <span class="rr-info-cell__value"
                                            id="profileEmailDisplay">{{ $userDetail->email }}</span>
                                        @if ($userDetail->email_verified_at)
                                            <span id="profileEmailBadge"
                                                style="font-size:0.74rem;color:#16a34a;font-weight:700;display:inline-flex;align-items:center;gap:4px;"><i
                                                    class="fa fa-circle-check"></i> Verified</span>
                                        @else
                                            <span id="profileEmailBadge"
                                                style="font-size:0.74rem;color:#dc2626;font-weight:700;display:inline-flex;align-items:center;gap:4px;"><i
                                                    class="fa fa-circle-exclamation"></i> Not Verified</span>
                                        @endif
                                    </div>

                                    <div class="rr-info-cell">
                                        <span class="rr-info-cell__label"><i class="fa fa-phone"></i> Phone</span>
                                        @if ($userDetail->phone)
                                            <span class="rr-info-cell__value">{{ $userDetail->phone }}</span>
                                        @else
                                            <span class="rr-info-cell__value"
                                                style="color:var(--rr-text-muted);font-weight:400;">Not set</span>
                                        @endif
                                    </div>

                                    <div class="rr-info-cell">
                                        <span class="rr-info-cell__label"><i class="fa fa-user"></i> Username</span>
                                        <span class="rr-info-cell__value">{{ $user->username }}</span>
                                    </div>

                                    <div class="rr-info-cell">
                                        <span class="rr-info-cell__label"><i class="fa fa-id-badge"></i> Consumer
                                            No</span>
                                        <span class="rr-info-cell__value">{{ $userDetail->consumer_no }}</span>
                                    </div>

                                    <div class="rr-info-cell">
                                        <span class="rr-info-cell__label"><i class="fa fa-lock"></i> Password</span>
                                        <span class="rr-info-cell__value">••••••••</span>
                                    </div>

                                    <div class="rr-info-cell">
                                        <span class="rr-info-cell__label">
                                            <i class="fa fa-circle-check"></i>
                                            Account Status
                                        </span>
                                        @if ($user->status == 1)
                                            <span class="rr-info-cell__value" style="color:#22c55e;">
                                                <i class="fa fa-circle"
                                                    style="font-size:0.6rem;margin-right:4px;"></i>Active
                                            </span>
                                        @else
                                            <span class="rr-info-cell__value" style="color:#ef4444;">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MEDICAL CARD TAB --}}
                    <div class="tab-pane fade" id="v-pills-medprofile" role="tabpanel">
                        <div class="rr-profile-card">
                            {{-- Card section header --}}
                            <div
                                style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:10px;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div
                                        style="width:44px;height:44px;border-radius:12px;background:var(--rr-grad-primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:var(--rr-shadow-primary);">
                                        <i class="fa fa-id-card-clip"></i>
                                    </div>

                                    <div>
                                        <h4 style="margin:0;font-size:1.1rem;">Medical ID Card</h4>
                                        <p style="margin:0;color:var(--rr-text-muted);font-size:0.82rem;">Shown to
                                            paramedics during emergencies</p>
                                    </div>
                                </div>

                                <button class="rr-btn rr-btn--primary" id="medCardAddBtn" data-bs-toggle="modal"
                                    data-bs-target="#addModal">
                                    @if ($medicalCard)
                                        <i class="fa fa-pen"></i> Update Medical Card
                                    @else
                                        <i class="fa fa-plus"></i> Add Medical Card
                                    @endif
                                </button>
                            </div>

                            {{-- Empty state (shown when no medical card) --}}
                            <div class="rr-med-card rr-med-card--empty {{ $medicalCard ? 'd-none' : '' }}"
                                id="medCardEmpty">
                                <div class="rr-med-empty-icon">
                                    <i class="fa fa-file-medical"></i>
                                </div>

                                <h4>No medical card on file</h4>
                                <p>Add your medical details so paramedics can help<br>you faster in an emergency.</p>

                                <button class="rr-btn rr-btn--primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                    <i class="fa fa-plus"></i> Add Medical Card
                                </button>
                            </div>

                            {{-- Filled state (shown when medical card exists) --}}
                            <div class="{{ $medicalCard ? '' : 'd-none' }}" id="medCardFilled">
                                <div class="rr-med-card">
                                    <div class="rr-med-card__header">
                                        <div class="rr-med-card__logo">
                                            <i class="fa fa-heart-pulse"></i>
                                            <span>Rapid Rescue · Medical ID</span>
                                        </div>

                                        <div class="rr-med-card__blood">
                                            <span>Blood Type</span>
                                            <strong id="mc_blood_type">{{ $medicalCard->blood_type ?? '—' }}</strong>
                                        </div>
                                    </div>

                                    <div class="rr-med-card__name">
                                        <p>Patient Name</p>
                                        <h4 id="mc_name">{{ $userDetail->first_name }} {{ $userDetail->last_name }}
                                        </h4>
                                    </div>

                                    <div class="rr-med-card__chips">
                                        @if ($medicalCard && $medicalCard->allergies)
                                            <span class="rr-med-chip rr-med-chip--alert">
                                                <i class="fa fa-triangle-exclamation"></i>
                                                {{ Str::limit($medicalCard->allergies, 25) }}
                                            </span>
                                        @endif

                                        @if ($medicalCard && $medicalCard->medical_history)
                                            <span class="rr-med-chip">
                                                <i class="fa fa-notes-medical"></i>
                                                {{ Str::limit($medicalCard->medical_history, 20) }}
                                            </span>
                                        @endif

                                        @if ($medicalCard && $medicalCard->medications)
                                            <span class="rr-med-chip">
                                                <i class="fa fa-pills"></i>
                                                {{ Str::limit($medicalCard->medications, 20) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="rr-med-card__footer">
                                        <div class="rr-med-card__contact">
                                            <p>Emergency Contact</p>
                                            <strong id="mc_contact_name">{{ $medicalCard->contact_name ?? '—' }}</strong>
                                            <small id="mc_contact_phone">{{ $medicalCard->contact_phone ?? '' }}</small>
                                        </div>

                                        <div class="rr-med-card__qr">
                                            <i class="fa fa-qrcode"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="rr-med-details">
                                    <div class="rr-med-detail-block">
                                        <h6><i class="fa fa-notes-medical"></i> Medical History</h6>
                                        <p id="mc_history">{{ $medicalCard->medical_history ?? '—' }}</p>
                                    </div>

                                    <div class="rr-med-detail-block">
                                        <h6><i class="fa fa-triangle-exclamation"></i> Allergies</h6>
                                        <p id="mc_allergies">{{ $medicalCard->allergies ?? '—' }}</p>
                                    </div>
                                </div>

                                <div class="rr-emergency-block">
                                    <div class="rr-emergency-block__icon">
                                        <i class="fa fa-phone-volume"></i>
                                    </div>

                                    <div class="rr-emergency-block__info">
                                        <p>Emergency Contact</p>
                                        <strong id="mc_relation">{{ $medicalCard->contact_name ?? '—' }}</strong>
                                        <small>
                                            <span id="mc_relation_label">{{ $medicalCard->relation ?? '' }}</span>
                                            {{ $medicalCard && $medicalCard->relation ? ' · ' : '' }}
                                            <span id="mc_contact_phone2">{{ $medicalCard->contact_phone ?? '—' }}</span>
                                        </small>
                                    </div>

                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <button class="rr-btn rr-btn--outline" data-bs-toggle="modal"
                                            data-bs-target="#addModal">
                                            <i class="fa fa-pen"></i> Edit
                                        </button>

                                        <button class="rr-btn rr-btn--outline" onclick="deleteMedicalCard()">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- MY BOOKINGS TAB --}}
                    <div class="tab-pane fade" id="v-pills-bookings" role="tabpanel">
                        <div class="rr-profile-card">
                            <div
                                style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--rr-border);">
                                <div
                                    style="width:48px;height:48px;border-radius:14px;background:var(--rr-grad-primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:var(--rr-shadow-primary);">
                                    <i class="fa fa-ambulance"></i>
                                </div>

                                <div>
                                    <h4 style="margin:0;font-size:1.1rem;">My Ambulance Bookings</h4>
                                    <p style="margin:0;color:var(--rr-text-muted);font-size:0.82rem;">Full history of your
                                        emergency requests</p>
                                </div>
                            </div>

                            {{-- rrBkEmptyState: always in DOM; hidden when bookings exist --}}
                            <div id="rrBkEmptyState" style="text-align:center;padding:50px 20px;{{ (isset($myBookings) && $myBookings->count()) ? 'display:none;' : '' }}">
                                <div
                                    style="width:72px;height:72px;border-radius:50%;background:var(--rr-primary-50);color:var(--rr-primary);display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:16px;">
                                    <i class="fa fa-ambulance"></i>
                                </div>
                                <h4 style="color:var(--rr-navy);margin-bottom:8px;">No bookings yet</h4>
                                <p style="color:var(--rr-text-muted);font-size:0.9rem;margin-bottom:20px;">Your
                                    emergency request history will appear here.</p>
                                <a href="{{ route('home') }}" class="rr-btn rr-btn--primary">
                                    <i class="fa fa-plus"></i> Book an Ambulance
                                </a>
                            </div>

                            <div id="rrBkTableWrap" data-user-id="{{ Auth::guard('users')->id() }}" style="overflow-x:auto; height: 60vh; {{ isset($myBookings) && $myBookings->count() ? '' : 'display:none;' }}">
                                <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
                                    <thead>
                                        <tr style="background:var(--rr-bg-soft);">
                                            <th
                                                style="padding:10px 14px;text-align:left;color:var(--rr-text-light);font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--rr-border);">
                                                #</th>
                                            <th
                                                style="padding:10px 14px;text-align:left;color:var(--rr-text-light);font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--rr-border);">
                                                Type</th>
                                            <th
                                                style="padding:10px 14px;text-align:left;color:var(--rr-text-light);font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--rr-border);">
                                                Pickup Address</th>
                                            <th
                                                style="padding:10px 14px;text-align:left;color:var(--rr-text-light);font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--rr-border);">
                                                Status</th>
                                            <th
                                                style="padding:10px 14px;text-align:left;color:var(--rr-text-light);font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--rr-border);">
                                                Date</th>
                                            <th
                                                style="padding:10px 14px;text-align:left;color:var(--rr-text-light);font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--rr-border);">
                                                Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($myBookings as $req)
                                            @php
                                                $sBadge = [
                                                    '1' => 'background:rgba(245,158,11,0.12);color:#b45309;',
                                                    '2' => 'background:rgba(59,130,246,0.12);color:#1d4ed8;',
                                                    '3' => 'background:rgba(139,92,246,0.12);color:#6d28d9;',
                                                    '4' => 'background:rgba(20,184,166,0.12);color:#0f766e;',
                                                    '5' => 'background:rgba(249,115,22,0.12);color:#c2410c;',
                                                    '6' => 'background:rgba(34,197,94,0.12);color:#166534;',
                                                    '7' => 'background:rgba(107,114,128,0.12);color:#374151;',
                                                    '8' => 'background:rgba(245,158,11,0.12);color:#b45309;',
                                                ];
                                                $sStyle =
                                                    $sBadge[$req->status] ?? 'background:#f3f4f6;color:#374151;';
                                            @endphp

                                            <tr id="rrBkRow_{{ $req->id }}" data-status="{{ $req->status }}"
                                                style="border-bottom:1px solid var(--rr-border);">
                                                <td style="padding:12px 14px;color:var(--rr-text-muted);">
                                                    {{ $loop->iteration }}</td>
                                                <td style="padding:12px 14px;">
                                                    <span
                                                        style="padding:3px 9px;border-radius:20px;font-size:0.73rem;font-weight:700;background:rgba(215,44,66,0.1);color:var(--rr-primary);">
                                                        @if ($req->type === '1')
                                                            Emergency
                                                        @elseif($req->type === '2')
                                                            non-emergency
                                                        @endif
                                                    </span>
                                                </td>
                                                <td
                                                    style="padding:12px 14px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $req->pickup_address }}</td>
                                                <td style="padding:12px 14px;">
                                                    <span id="rrBkBadge_{{ $req->id }}"
                                                        style="padding:3px 10px;border-radius:20px;font-size:0.73rem;font-weight:700;{{ $sStyle }}">
                                                        @if ($req->status === '1')
                                                            Pending
                                                        @elseif($req->status === '2')
                                                            Dispatched
                                                        @elseif($req->status === '3')
                                                            On Way
                                                        @elseif($req->status === '4')
                                                            Arrived
                                                        @elseif($req->status === '5')
                                                            Transporting
                                                        @elseif($req->status === '6')
                                                            Completed
                                                        @elseif($req->status === '7')
                                                            Cancelled
                                                        @elseif($req->status === '8')
                                                            Pending
                                                        @endif
                                                    </span>
                                                </td>
                                                <td
                                                    style="padding:12px 14px;color:var(--rr-text-muted);white-space:nowrap;">
                                                    {{ $req->created_at->format('d M Y') }}</td>
                                                <td style="padding:12px 14px;">
                                                    <a href="{{ route('tracking', $req->id) }}"
                                                        class="rr-btn rr-btn--primary"
                                                        style="padding:5px 12px;font-size:0.78rem;">
                                                        <i class="fa fa-map-location-dot"></i> Track
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- CONTACT HISTORY TAB --}}
                    <div class="tab-pane fade" id="v-pills-contacts" role="tabpanel">
                        <div class="rr-profile-card">
                            <div
                                style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--rr-border);">
                                <div
                                    style="width:48px;height:48px;border-radius:14px;background:var(--rr-grad-primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:var(--rr-shadow-primary);">
                                    <i class="fa fa-envelope-open-text"></i>
                                </div>

                                <div>
                                    <h4 style="margin:0;font-size:1.1rem;">Contact History</h4>
                                    <p style="margin:0;color:var(--rr-text-muted);font-size:0.82rem;">Messages you have
                                        sent to our support team</p>
                                </div>
                            </div>

                            {{-- Empty state (hidden when messages exist) --}}
                            <div id="contactEmptyState"
                                style="text-align:center;padding:40px 20px;{{ $contactMessages->isNotEmpty() ? 'display:none;' : '' }}">
                                <div
                                    style="width:64px;height:64px;border-radius:50%;background:var(--rr-primary-50);color:var(--rr-primary);display:inline-flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:14px;">
                                    <i class="fa fa-inbox"></i>
                                </div>
                                <h5 style="color:var(--rr-navy);margin:0 0 8px;">No messages yet</h5>
                                <p style="color:var(--rr-text-muted);margin:0 0 20px;font-size:0.9rem;">You haven't
                                    contacted us yet. Use the contact form on the home page.</p>
                                <a href="/#contact" class="rr-btn rr-btn--primary"><i class="fa fa-message"></i> Contact
                                    Us</a>
                            </div>

                            {{-- Two-panel chat layout (hidden when empty) --}}
                            <div id="contactMsgListPanel"
                                style="display:flex;gap:0;border:1px solid var(--rr-border);border-radius:var(--rr-radius);overflow:hidden;min-height:480px;{{ $contactMessages->isEmpty() ? 'display:none;' : '' }}">
                                {{-- Message list --}}
                                <div
                                    style="width:280px;flex-shrink:0;border-right:1px solid var(--rr-border);background:var(--rr-bg-soft);overflow-y:auto;">
                                    <div id="contactMsgList">
                                        @foreach ($contactMessages as $msg)
                                            @php $hasUnread = $msg->replies->where('sender_type', 'admin')->where('is_read', false)->count() > 0; @endphp
                                            <div class="rr-msg-item {{ $msg->replies->count() > 0 ? 'has-replies' : '' }}"
                                                id="msgItem{{ $msg->id }}" data-id="{{ $msg->id }}"
                                                data-subject="{{ htmlspecialchars($msg->subject, ENT_QUOTES) }}"
                                                data-message="{{ htmlspecialchars($msg->message, ENT_QUOTES) }}"
                                                data-date="{{ $msg->created_at->format('d M Y, h:i A') }}"
                                                onclick="openContactThread({{ $msg->id }}, this)"
                                                style="padding:14px 16px;border-bottom:1px solid var(--rr-border);cursor:pointer;transition:background .15s;{{ $hasUnread ? 'border-left:3px solid var(--rr-primary);' : '' }}">
                                                <div
                                                    style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                                                    <strong
                                                        style="font-size:0.88rem;color:var(--rr-navy);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $msg->subject }}</strong>
                                                    <span
                                                        style="font-size:0.7rem;color:var(--rr-text-light);flex-shrink:0;white-space:nowrap;">{{ $msg->created_at->format('d M') }}</span>
                                                </div>
                                                <div
                                                    style="font-size:0.78rem;color:var(--rr-text-muted);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                    {{ Str::limit($msg->message, 45) }}</div>
                                                @if ($hasUnread)
                                                    <span id="unreadBadge{{ $msg->id }}"
                                                        style="display:inline-flex;align-items:center;gap:4px;background:var(--rr-primary);color:#fff;font-size:0.62rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-top:6px;letter-spacing:0.03em;">
                                                        <i class="fa fa-circle" style="font-size:0.4rem;"></i>NEW REPLY
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Chat thread --}}
                                <div style="flex:1;display:flex;flex-direction:column;min-width:0;">
                                    {{-- Thread header --}}
                                    <div id="contactThreadHeader"
                                        style="padding:16px 20px;border-bottom:1px solid var(--rr-border);background:#fff;">
                                        <div style="color:var(--rr-text-muted);font-size:0.87rem;">
                                            <i class="fa fa-arrow-left" style="margin-right:8px;"></i>Select a message to
                                            view the thread
                                        </div>
                                    </div>
                                    {{-- Messages --}}
                                    <div id="contactThreadBody"
                                        style="flex:1;height:60vh;max-height:60vh;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;background:var(--rr-bg-light, #f8fafc);">
                                        <div
                                            style="text-align:center;color:var(--rr-text-light);font-size:0.87rem;padding:40px 0;">
                                            <i class="fa fa-envelope-open-text"
                                                style="font-size:2rem;display:block;margin-bottom:12px;opacity:0.3;"></i>
                                            Click a message on the left to open the conversation
                                        </div>
                                    </div>
                                    {{-- Reply input --}}
                                    <div id="contactReplyFooter"
                                        style="display:none;padding:14px 16px;border-top:1px solid var(--rr-border);background:#fff;">
                                        <div id="rrResolvedNotice"
                                            style="display:none;padding:10px 14px;background:rgba(34,197,94,0.07);border-radius:8px;color:#15803d;font-size:0.82rem;font-weight:600;text-align:center;border:1px solid rgba(34,197,94,0.25);margin-bottom:10px;">
                                            <i class="fa fa-circle-check"></i> This conversation has been resolved. No new
                                            messages can be sent.
                                        </div>
                                        <div id="rrAdminTypingIndicator"
                                            style="display:none;font-size:0.78rem;color:var(--rr-text-muted);padding:0 2px 8px;align-items:center;gap:5px;">
                                            <span>Operator is typing</span>
                                            <span
                                                style="display:inline-block;width:4px;height:4px;border-radius:50%;background:var(--rr-text-muted);margin:0 1px;animation:rrTypingBounce 1.2s infinite;"></span>
                                            <span
                                                style="display:inline-block;width:4px;height:4px;border-radius:50%;background:var(--rr-text-muted);margin:0 1px;animation:rrTypingBounce 1.2s infinite .2s;"></span>
                                            <span
                                                style="display:inline-block;width:4px;height:4px;border-radius:50%;background:var(--rr-text-muted);margin:0 1px;animation:rrTypingBounce 1.2s infinite .4s;"></span>
                                        </div>
                                        <div style="display:flex;gap:8px;">
                                            <textarea id="userReplyInput" rows="1"
                                                style="flex:1;resize:none;border-radius:10px;border:1px solid var(--rr-border);padding:10px 14px;font-size:0.88rem;font-family:inherit;color:var(--rr-navy);outline:none;min-height:44px;max-height:100px;"
                                                placeholder="Type a follow-up message…"></textarea>
                                            <button class="rr-btn rr-btn--primary" id="userSendBtn"
                                                style="padding:10px 18px;border-radius:10px;">
                                                <i class="fa fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FIRST-AID TAB --}}
                    <div class="tab-pane fade" id="v-pills-instructions" role="tabpanel">
                        <div class="rr-profile-card">
                            <div
                                style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--rr-border);">
                                <div
                                    style="width:48px;height:48px;border-radius:14px;background:var(--rr-grad-primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;box-shadow:var(--rr-shadow-primary);">
                                    <i class="fa fa-kit-medical"></i>
                                </div>

                                <div>
                                    <h4 style="margin:0;font-size:1.1rem;">First-Aid Quick Guide</h4>
                                    <p style="margin:0;color:var(--rr-text-muted);font-size:0.82rem;">Follow these steps
                                        while waiting for paramedics</p>
                                </div>
                            </div>

                            @php
                                $tips = [
                                    [
                                        'icon' => 'fa-shield-heart',
                                        'title' => 'Ensure Safety',
                                        'body' =>
                                            'Stay calm and make sure the scene is safe before approaching the injured person.',
                                    ],
                                    [
                                        'icon' => 'fa-phone-volume',
                                        'title' => 'Call Emergency Services',
                                        'body' =>
                                            'Dial emergency services immediately. Share your exact location and describe the situation clearly.',
                                    ],
                                    [
                                        'icon' => 'fa-hand-holding-heart',
                                        'title' => 'Provide First Aid',
                                        'body' =>
                                            'If you are trained, administer CPR or first aid as necessary. Do not move the person unless in immediate danger.',
                                    ],
                                    [
                                        'icon' => 'fa-temperature-low',
                                        'title' => 'Keep Them Comfortable',
                                        'body' =>
                                            'Keep the person warm and still. Reassure them help is on the way. Monitor breathing until paramedics arrive.',
                                    ],
                                ];
                            @endphp

                            <div style="display:flex;flex-direction:column;gap:12px;">
                                @foreach ($tips as $i => $tip)
                                    <div
                                        style="display:flex;gap:16px;padding:20px;background:var(--rr-bg-soft);border:1px solid var(--rr-border);border-radius:var(--rr-radius);align-items:flex-start;">
                                        <div
                                            style="flex-shrink:0;width:40px;height:40px;border-radius:12px;background:var(--rr-grad-primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:0.95rem;box-shadow:var(--rr-shadow-primary);">
                                            <i class="fa {{ $tip['icon'] }}"></i>
                                        </div>

                                        <div>
                                            <strong
                                                style="display:block;color:var(--rr-navy);margin-bottom:4px;">{{ $tip['title'] }}</strong>
                                            <p style="margin:0;color:var(--rr-text-muted);font-size:0.92rem;">
                                                {{ $tip['body'] }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DELETE MEDICAL CARD CONFIRMATION MODAL --}}
    <div class="modal fade" id="deleteMedCardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid var(--rr-border);padding:20px 24px;">
                    <h5 class="modal-title" style="font-weight:700;color:var(--rr-navy);">
                        <i class="fa fa-triangle-exclamation" style="color:#ef4444;margin-right:8px;"></i> Delete Medical
                        Card
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <p style="color:var(--rr-text-muted);margin:0;">Are you sure you want to delete your medical card? This
                        action <strong>cannot be undone</strong> and paramedics will no longer have access to your medical
                        information.</p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 24px;gap:10px;">
                    <button type="button" class="rr-btn rr-btn--outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="rr-btn rr-btn--primary"
                        style="background:#ef4444;border-color:#ef4444;" onclick="confirmDeleteMedicalCard()">
                        <i class="fa fa-trash"></i> Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- EDIT PROFILE MODAL                                           --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border:none;border-radius:var(--rr-radius-lg);overflow:hidden;">
                <div class="modal-header" style="background:var(--rr-grad-hero);padding:20px 26px;border:none;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);color:#fff;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fa fa-pen"></i>
                        </div>

                        <div>
                            <h5 class="modal-title" style="font-weight:800;color:#fff;margin:0;">Edit Profile</h5>
                            <small style="color:rgba(255,255,255,0.65);font-size:0.78rem;">Update your personal
                                information</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="padding:26px;">
                    <form id="editProfileForm" enctype="multipart/form-data">
                        @csrf
                        {{-- Photo upload --}}
                        <label class="rr-photo-upload" for="photoUploadInput">
                            <img src="{{ asset('assets/user/img/users/' . ($userDetail->profile_picture ?? 'default.jpg')) }}"
                                alt="Profile photo" class="rr-photo-upload__preview" id="photoPreview">
                            <div class="rr-photo-upload__text">
                                <strong><i class="fa fa-camera"
                                        style="color:var(--rr-primary);margin-right:6px;"></i>Update Profile Photo</strong>
                                <small>JPG, PNG or GIF · Max 2 MB</small>
                            </div>
                            <input type="file" id="photoUploadInput" name="image" accept="image/*">
                        </label>

                        {{-- Name --}}
                        <div class="rr-modal-section"><span>Personal Info</span></div>
                        <div class="rr-field">
                            <label class="rr-label">Username</label>
                            <div class="rr-input-group">
                                <i class="fa fa-user"></i>
                                <input class="rr-input" type="text" id="edit_username" name="edit_username"
                                    maxlength="30" oninput="validateUsername(this)" placeholder="Username"
                                    value="{{ $user->username }}">
                            </div>
                            <small id="username_feedback" style="display:none;font-size:0.82rem;margin-top:4px;"></small>
                        </div>

                        <div class="rr-row">
                            <div class="rr-field">
                                <label class="rr-label">First Name</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-user"></i>
                                    <input class="rr-input" type="text" id="edit_first_name" name="edit_first_name"
                                        maxlength="15" oninput="allowOnlyAlphabets(this)" placeholder="First name"
                                        value="{{ $userDetail->first_name }}">
                                </div>
                            </div>

                            <div class="rr-field">
                                <label class="rr-label">Last Name</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-user"></i>
                                    <input class="rr-input" type="text" id="edit_last_name" name="edit_last_name"
                                        maxlength="15" oninput="allowOnlyAlphabets(this)" placeholder="Last name"
                                        value="{{ $userDetail->last_name }}">
                                </div>
                            </div>
                        </div>

                        <div class="rr-row">
                            <div class="rr-field">
                                <label class="rr-label">Email Address</label>
                                <div
                                    style="display:flex;align-items:center;gap:10px;background:var(--rr-bg-soft);border:1px solid var(--rr-border);border-radius:10px;padding:11px 14px;">
                                    <i class="fa fa-envelope" style="color:var(--rr-primary);flex-shrink:0;"></i>
                                    <span id="editModalEmailDisplay"
                                        style="flex:1;font-size:0.93rem;color:var(--rr-navy);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $userDetail->email }}</span>
                                    @if ($userDetail->email_verified_at)
                                        <span id="editModalEmailBadge"
                                            style="font-size:0.70rem;color:#16a34a;font-weight:700;white-space:nowrap;flex-shrink:0;"><i
                                                class="fa fa-circle-check"></i> Verified</span>
                                    @else
                                        <span id="editModalEmailBadge"
                                            style="font-size:0.70rem;color:#dc2626;font-weight:700;white-space:nowrap;flex-shrink:0;"><i
                                                class="fa fa-circle-exclamation"></i> Not Verified</span>
                                    @endif
                                </div>
                                <button type="button" onclick="openChangeEmailModal()"
                                    style="margin-top:6px;background:none;border:none;color:var(--rr-primary);font-size:0.82rem;font-weight:700;cursor:pointer;padding:2px 0;display:inline-flex;align-items:center;gap:5px;"><i
                                        class="fa fa-pen" style="font-size:0.75rem;"></i> Change Email Address</button>
                            </div>

                            <div class="rr-field">
                                <label class="rr-label">Phone</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-phone"></i>
                                    <input class="rr-input" type="text" id="edit_phone" name="edit_phone"
                                        value="{{ $userDetail->phone }}" oninput="validatePakPhone(this)">
                                </div>
                                <small id="phone_error" class="text-danger field-error"></small>
                                <small id="phone_feedback" class="text-danger"
                                    style="display:none;font-size:0.82rem;margin-top:2px;"></small>
                            </div>
                        </div>

                        <div class="rr-row">
                            <div class="rr-field">
                                <label class="rr-label">Date of Birth</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-cake-candles"></i>
                                    <input class="rr-input" type="date" id="edit_dob" name="edit_dob"
                                        value="{{ $userDetail->date_of_birth ? $userDetail->date_of_birth->format('Y-m-d') : '' }}">
                                </div>
                            </div>

                            <div class="rr-field">
                                <label class="rr-label">Address</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-location-dot"></i>
                                    <input class="rr-input" type="text" id="edit_address" name="edit_address"
                                        value="{{ $userDetail->address ?? '' }}" placeholder="Your address"
                                        maxlength="100">
                                </div>
                            </div>
                        </div>

                        {{-- Security --}}
                        <div class="rr-modal-section"><span>Security</span></div>
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;background:var(--rr-bg-soft);border:1px solid var(--rr-border);border-radius:10px;padding:14px 16px;gap:12px;">
                            <div>
                                <div
                                    style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--rr-text-light);margin-bottom:3px;">
                                    Password</div>
                                <div style="font-size:1rem;font-weight:600;color:var(--rr-navy);letter-spacing:3px;">
                                    ••••••••</div>
                            </div>
                            <button type="button" class="rr-btn rr-btn--light" onclick="openChangePasswordModal()"
                                style="flex-shrink:0;">
                                <i class="fa fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 26px;">
                    <button type="button" class="rr-btn rr-btn--light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="update-Btn" class="rr-btn rr-btn--primary" onclick="submitEditProfile()">
                        <i class="fa fa-check"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- CHANGE EMAIL MODAL --}}
    <div class="modal fade" id="changeEmailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
            <div class="modal-content" style="border:none;border-radius:var(--rr-radius-lg);overflow:hidden;">
                <div class="modal-header" style="background:var(--rr-grad-hero);padding:20px 26px;border:none;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);color:#fff;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fa fa-envelope-circle-check"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" style="font-weight:800;color:#fff;margin:0;">Change Email Address</h5>
                            <small style="color:rgba(255,255,255,0.70);font-size:0.78rem;">Verify your new email to update
                                it</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- Step 1: Enter new email --}}
                <div id="changeEmailStep1">
                    <div class="modal-body" style="padding:26px;">
                        <form id="changeEmailForm">
                            @csrf
                            <div class="rr-field" style="margin-bottom:0;">
                                <label class="rr-label">New Email Address</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-envelope"></i>
                                    <input class="rr-input" type="email" id="new_email" name="new_email"
                                        maxlength="100" placeholder="Enter your new email">
                                </div>
                                <small id="new_email_error" class="text-danger"
                                    style="display:none;font-size:0.82rem;margin-top:4px;"></small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 26px;">
                        <button type="button" class="rr-btn rr-btn--light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="sendEmailCodeBtn" class="rr-btn rr-btn--primary"
                            onclick="sendEmailChangeCode()">
                            <i class="fa fa-paper-plane"></i> Send Verification Code
                        </button>
                    </div>
                </div>

                {{-- Step 2: Enter verification code --}}
                <div id="changeEmailStep2" class="d-none">
                    <div class="modal-body" style="padding:26px;">
                        <div style="text-align:center;margin-bottom:20px;">
                            <div
                                style="width:60px;height:60px;border-radius:50%;background:var(--rr-primary-50);color:var(--rr-primary);display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:12px;">
                                <i class="fa fa-envelope-open-text"></i>
                            </div>
                            <p style="margin:0;font-size:0.9rem;color:var(--rr-text-muted);">We sent a 6-digit code to</p>
                            <strong id="pendingEmailDisplay" style="color:var(--rr-navy);font-size:0.95rem;"></strong>
                            <p style="margin:4px 0 0;font-size:0.80rem;color:var(--rr-text-light);">Code expires in 1
                                minute.</p>
                        </div>
                        <form id="emailChangeVerifyForm">
                            @csrf
                            <div class="rr-field" style="margin-bottom:0;">
                                <label class="rr-label">Verification Code</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-shield-halved"></i>
                                    <input class="rr-input" type="text" id="email_change_code"
                                        name="email_change_code" maxlength="6" placeholder="••••••" inputmode="numeric"
                                        oninput="onEmailChangeCodeInput(this)"
                                        style="letter-spacing:6px;font-size:1.1rem;font-weight:700;">
                                </div>
                                <small id="email_change_code_error" class="text-danger"
                                    style="display:none;font-size:0.82rem;margin-top:4px;"></small>
                            </div>
                            <div
                                style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <button type="button" id="resendEmailChangeBtn" class="rr-btn rr-btn--light rr-btn--sm"
                                    onclick="resendEmailChangeCode()" disabled>
                                    <i class="fa fa-rotate-right"></i> Resend (<span
                                        id="resendEmailChangeTimer">60</span>s)
                                </button>
                                <small id="emailResendLimitMsg"
                                    style="display:none;font-size:0.80rem;color:#dc2626;font-weight:600;"></small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 26px;">
                        <button type="button" class="rr-btn rr-btn--light" onclick="emailChangeGoBack()"><i
                                class="fa fa-arrow-left"></i> Back</button>
                        <button type="button" id="verifyEmailChangeBtn" class="rr-btn rr-btn--primary"
                            onclick="verifyEmailChange()" disabled>
                            <i class="fa fa-check"></i> Verify & Update Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHANGE PASSWORD MODAL --}}
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
            <div class="modal-content" style="border:none;border-radius:var(--rr-radius-lg);overflow:hidden;">
                <div class="modal-header" style="background:var(--rr-grad-hero);padding:20px 26px;border:none;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);color:#fff;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fa fa-key"></i>
                        </div>
                        <div>
                            <h5 class="modal-title" style="font-weight:800;color:#fff;margin:0;">Change Password</h5>
                            <small style="color:rgba(255,255,255,0.70);font-size:0.78rem;">Verify your identity before
                                changing</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- Step 1: Enter new password --}}
                <div id="changePwdStep1">
                    <div class="modal-body" style="padding:26px;">
                        <form id="changePwdForm">
                            @csrf
                            <div class="rr-field">
                                <label class="rr-label">New Password</label>
                                <div class="rr-input-group rr-input-group--eye">
                                    <i class="fa fa-lock"></i>
                                    <input class="rr-input" type="password" id="new_pwd" name="new_password"
                                        oninput="validateNewPwd(this)" placeholder="At least 7 characters">
                                    <button type="button" class="rr-eye-toggle"
                                        onclick="togglePassword('new_pwd', this)" aria-label="Show password"><i
                                            class="fa fa-eye"></i></button>
                                </div>
                                <small id="new_pwd_error" class="text-danger" style="font-size:0.82rem;"></small>
                            </div>
                            <div class="rr-field" style="margin-bottom:0;">
                                <label class="rr-label">Confirm New Password</label>
                                <div class="rr-input-group rr-input-group--eye">
                                    <i class="fa fa-lock"></i>
                                    <input class="rr-input" type="password" id="confirm_new_pwd" name="confirm_password"
                                        oninput="validateConfirmNewPwd(this)" placeholder="Repeat password">
                                    <button type="button" class="rr-eye-toggle"
                                        onclick="togglePassword('confirm_new_pwd', this)" aria-label="Show password"><i
                                            class="fa fa-eye"></i></button>
                                </div>
                                <small id="confirm_new_pwd_error" class="text-danger" style="font-size:0.82rem;"></small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 26px;">
                        <button type="button" class="rr-btn rr-btn--light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="sendPwdCodeBtn" class="rr-btn rr-btn--primary"
                            onclick="sendPasswordChangeCode()">
                            <i class="fa fa-paper-plane"></i> Send Verification Code
                        </button>
                    </div>
                </div>

                {{-- Step 2: Enter verification code --}}
                <div id="changePwdStep2" class="d-none">
                    <div class="modal-body" style="padding:26px;">
                        <div style="text-align:center;margin-bottom:20px;">
                            <div
                                style="width:60px;height:60px;border-radius:50%;background:var(--rr-primary-50);color:var(--rr-primary);display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:12px;">
                                <i class="fa fa-envelope-open-text"></i>
                            </div>
                            <p style="margin:0;font-size:0.9rem;color:var(--rr-text-muted);">We sent a code to your
                                registered email</p>
                            <strong id="pwdChangeMaskedEmail" style="color:var(--rr-navy);font-size:0.95rem;"></strong>
                            <p style="margin:4px 0 0;font-size:0.80rem;color:var(--rr-text-light);">Code expires in 1
                                minute.</p>
                        </div>
                        <form id="pwdChangeVerifyForm">
                            @csrf
                            <div class="rr-field" style="margin-bottom:0;">
                                <label class="rr-label">Verification Code</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-shield-halved"></i>
                                    <input class="rr-input" type="text" id="pwd_change_code" name="pwd_change_code"
                                        maxlength="6" placeholder="••••••" inputmode="numeric"
                                        oninput="onPwdChangeCodeInput(this)"
                                        style="letter-spacing:6px;font-size:1.1rem;font-weight:700;">
                                </div>
                                <small id="pwd_change_code_error" class="text-danger"
                                    style="display:none;font-size:0.82rem;margin-top:4px;"></small>
                            </div>
                            <div
                                style="margin-top:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                <button type="button" id="resendPwdChangeBtn" class="rr-btn rr-btn--light rr-btn--sm"
                                    onclick="resendPasswordChangeCode()" disabled>
                                    <i class="fa fa-rotate-right"></i> Resend (<span id="resendPwdTimer">60</span>s)
                                </button>
                                <small id="pwdResendLimitMsg"
                                    style="display:none;font-size:0.80rem;color:#dc2626;font-weight:600;"></small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 26px;">
                        <button type="button" class="rr-btn rr-btn--light" onclick="pwdChangeGoBack()"><i
                                class="fa fa-arrow-left"></i> Back</button>
                        <button type="button" id="verifyPwdChangeBtn" class="rr-btn rr-btn--primary"
                            onclick="verifyAndChangePassword()" disabled>
                            <i class="fa fa-check"></i> Verify & Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ADD MEDICAL CARD MODAL                                       --}}
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border:none;border-radius:var(--rr-radius-lg);overflow:hidden;">
                <div class="modal-header" style="background:var(--rr-grad-primary);padding:20px 26px;border:none;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);color:#fff;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fa fa-id-card-clip"></i>
                        </div>

                        <div>
                            <h5 class="modal-title" style="font-weight:800;color:#fff;margin:0;">Add Medical Card</h5>
                            <small style="color:rgba(255,255,255,0.70);font-size:0.78rem;">This info is shared with
                                paramedics during emergencies</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="padding:26px;">
                    <form id="addMedicalForm">
                        @csrf
                        {{-- Medical info --}}
                        <div class="rr-modal-section"><span>Medical Information</span></div>
                        <div class="rr-field">
                            <label class="rr-label">Blood Type</label>
                            <div class="rr-input-group">
                                <i class="fa fa-droplet"></i>
                                <select class="rr-input" id="blood_type" name="blood_type" style="padding-left:46px;">
                                    <option value="0">Select blood type</option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'A+' ? 'selected' : '' }}>A+
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'A-' ? 'selected' : '' }}>A-
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'B+' ? 'selected' : '' }}>B+
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'B-' ? 'selected' : '' }}>B-
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'AB+' ? 'selected' : '' }}>AB+
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'AB-' ? 'selected' : '' }}>AB-
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'O+' ? 'selected' : '' }}>O+
                                    </option>
                                    <option {{ $medicalCard && $medicalCard->blood_type == 'O-' ? 'selected' : '' }}>O-
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Medical History / Conditions</label>
                            <div class="rr-input-group">
                                <i class="fa fa-notes-medical"></i>
                                <input class="rr-input" type="text" id="medical_history" name="medical_history"
                                    maxlength="500" oninput="allowMedField(this)"
                                    placeholder="e.g. Asthma, Diabetes, Hypertension…"
                                    value="{{ $medicalCard->medical_history ?? '' }}">
                            </div>
                            <small style="color:var(--rr-text-light);font-size:0.78rem;">Only letters, spaces and commas
                                allowed.</small>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Allergies</label>
                            <div class="rr-input-group">
                                <i class="fa fa-triangle-exclamation"></i>
                                <input class="rr-input" type="text" required id="allergies" name="allergies"
                                    maxlength="500" oninput="allowMedField(this)"
                                    placeholder="e.g. Penicillin, Peanuts, Latex…"
                                    value="{{ $medicalCard->allergies ?? '' }}">
                            </div>
                            <small style="color:var(--rr-text-light);font-size:0.78rem;">Only letters, spaces and commas
                                allowed.</small>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Current Medications</label>
                            <div class="rr-input-group">
                                <i class="fa fa-pills"></i>
                                <input class="rr-input" required type="text" id="medications" name="medications"
                                    maxlength="500" oninput="allowMedField(this)"
                                    placeholder="e.g. Salbutamol inhaler, Metformin…"
                                    value="{{ $medicalCard->medications ?? '' }}">
                            </div>
                            <small style="color:var(--rr-text-light);font-size:0.78rem;">Only letters, spaces and commas
                                allowed.</small>
                        </div>

                        {{-- Emergency contact --}}
                        <div class="rr-modal-section"><span>Emergency Contact</span></div>

                        <div class="rr-med-note">
                            <i class="fa fa-circle-info"></i>
                            This person will be contacted by paramedics if you are unable to respond.
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Contact Full Name</label>
                            <div class="rr-input-group">
                                <i class="fa fa-user-shield"></i>
                                <input class="rr-input" required type="text" id="contact_name" name="contact_name"
                                    maxlength="20" oninput="allowOnlyAlphabets(this)" placeholder="e.g. Akram Ahmed"
                                    value="{{ $medicalCard->contact_name ?? '' }}">
                            </div>
                        </div>

                        <div class="rr-row">
                            <div class="rr-field">
                                <label class="rr-label">Relation</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-people-group"></i>
                                    <input class="rr-input" required type="text" id="relation" name="relation"
                                        maxlength="20" oninput="allowOnlyAlphabets(this)"
                                        placeholder="e.g. Father, Spouse…" value="{{ $medicalCard->relation ?? '' }}">
                                </div>
                            </div>

                            <div class="rr-field">
                                <label class="rr-label">Contact Number</label>
                                <div class="rr-input-group">
                                    <i class="fa fa-phone"></i>
                                    <input class="rr-input" required type="text" id="contact_phone"
                                        name="contact_phone" oninput="validatePakPhone(this)" placeholder="03xxxxxxxxx"
                                        value="{{ $medicalCard->contact_phone ?? '' }}">
                                </div>
                                <small id="contact_phone_error" class="text-danger field-error"></small>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--rr-border);padding:16px 26px;">
                    <button type="button" class="rr-btn rr-btn--light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="med-save-Btn" class="rr-btn rr-btn--primary"
                        onclick="submitMedicalCard()">
                        <i class="fa fa-heart-pulse"></i> Save Medical Card
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.routes = {
            checkAvail: "{{ route('checkAvailability') }}",
            updateProfile: "{{ route('profile.update') }}",
            profileEmailSendCode: "{{ route('profile.email.sendCode') }}",
            profileEmailResend: "{{ route('profile.email.resend') }}",
            profileEmailVerify: "{{ route('profile.email.verify') }}",
            profilePasswordSendCode: "{{ route('profile.password.sendCode') }}",
            profilePasswordResend: "{{ route('profile.password.resend') }}",
            profilePasswordChange: "{{ route('profile.password.change') }}",
            storeMedicalCard: "{{ route('medicalCard.store') }}",
            deleteMedicalCard: "{{ route('medicalCard.delete') }}",
            contactReply: "{{ url('/contact-messages') }}",
            contactThread: "{{ url('/contact-messages') }}",
            contactHistory: "{{ route('contact-history') }}",
            csrfToken: "{{ csrf_token() }}"
        };
        window.profileName = "{{ $userDetail->first_name }} {{ $userDetail->last_name }}";
    </script>

    <!-- CUSTOM JS -->
    <script src="{{ asset('assets/user/js/profile.js') }}"></script>
@endsection

@push('scripts')
    <script src="{{ asset('assets/user/js/profileContact.js') }}"></script>
    <script src="{{ asset('assets/user/js/bookings-realtime.js') }}"></script>
@endpush