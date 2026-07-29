@extends('user.layouts.user')
@section('title', 'Terms of Service | Rapid Rescue')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/user/css/pptc.css') }}">
@endpush

@section('content')
    {{-- Hero --}}
    <section class="rr-legal-hero">
        <div class="container text-center">
            <div class="rr-legal-hero__badge">
                <i class="fa fa-file-contract"></i> Legal Document
            </div>
            <h1>Terms of Service</h1>
            <p>Please read these terms carefully before using the Rapid Rescue platform.</p>
        </div>
    </section>

    {{-- Body --}}
    <div class="container py-5 px-3 px-md-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-9">
                <div class="rr-legal-updated">
                    <i class="fa fa-calendar-check"></i>
                    Last updated: {{ date('d F Y') }} &nbsp;|&nbsp; Effective immediately upon account creation.
                </div>

                <div class="rr-legal-toc">
                    <h6><i class="fa fa-list"></i> Table of Contents</h6>
                    <ol>
                        <li><a href="#acceptance">Acceptance of Terms</a></li>
                        <li><a href="#services">Our Services</a></li>
                        <li><a href="#accounts">User Accounts</a></li>
                        <li><a href="#emergency">Emergency Use</a></li>
                        <li><a href="#conduct">Acceptable Use</a></li>
                        <li><a href="#medical">Medical Disclaimer</a></li>
                        <li><a href="#privacy">Privacy</a></li>
                        <li><a href="#liability">Limitation of Liability</a></li>
                        <li><a href="#termination">Termination</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ol>
                </div>

                <div class="rr-legal-section" id="acceptance">
                    <h2><i class="fa fa-handshake"></i> 1. Acceptance of Terms</h2>
                    <p>By accessing or using the Rapid Rescue platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree with any part of these Terms, you may not access the Service.</p>
                    <p>These Terms apply to all users, including visitors, registered users, and administrators. We reserve the right to update these Terms at any time. Continued use of the Service after changes constitutes your acceptance of the revised Terms.</p>
                </div>

                <div class="rr-legal-section" id="services">
                    <h2><i class="fa fa-ambulance"></i> 2. Our Services</h2>
                    <p>Rapid Rescue is an e-ambulance dispatch platform that allows users to:</p>
                    <ul>
                        <li>Request emergency ambulance services in real time.</li>
                        <li>Maintain a personal Medical ID card accessible to paramedics during emergencies.</li>
                        <li>Track dispatched ambulances via GPS in real time.</li>
                        <li>Review a complete history of past emergency requests.</li>
                        <li>Receive real-time notifications from dispatch to arrival.</li>
                    </ul>
                    <p>We strive to dispatch services as quickly as possible, but availability may depend on geographic location, traffic conditions, and resource availability.</p>
                </div>

                <div class="rr-legal-section" id="accounts">
                    <h2><i class="fa fa-user-shield"></i> 3. User Accounts</h2>
                    <p>To use core features, you must create an account. You agree to:</p>
                    <ul>
                        <li>Provide accurate, current, and complete information during registration.</li>
                        <li>Keep your password confidential and notify us immediately of any unauthorized use.</li>
                        <li>Be responsible for all activity that occurs under your account.</li>
                        <li>Not share your account credentials with any third party.</li>
                    </ul>
                    <p>We reserve the right to suspend or terminate accounts that contain false information or violate these Terms.
                    </p>
                </div>

                <div class="rr-legal-section" id="emergency">
                    <h2><i class="fa fa-triangle-exclamation"></i> 4. Emergency Use</h2>
                    <p>Rapid Rescue is designed to supplement, not replace, official government emergency services (e.g., dialing 1122 or 115). In a life-threatening emergency, always contact official emergency services first.</p>
                    <p>Abuse of the emergency request system — including submitting false or prank emergency requests — is strictly prohibited and may result in immediate account termination and legal action.</p>
                </div>

                <div class="rr-legal-section" id="conduct">
                    <h2><i class="fa fa-shield-halved"></i> 5. Acceptable Use</h2>
                    <p>You agree not to:</p>
                    <ul>
                        <li>Submit false emergency requests or misuse the dispatch system.</li>
                        <li>Attempt to reverse-engineer, hack, or exploit the platform.</li>
                        <li>Upload malicious files or content that harms the service or other users.</li>
                        <li>Impersonate another person or entity.</li>
                        <li>Use the platform for any unlawful purpose.</li>
                    </ul>
                </div>

                <div class="rr-legal-section" id="medical">
                    <h2><i class="fa fa-notes-medical"></i> 6. Medical Disclaimer</h2>
                    <p>The Medical ID card and first-aid guides provided on Rapid Rescue are for informational purposes only. They do not constitute professional medical advice, diagnosis, or treatment.</p>
                    <p>Always seek the advice of a qualified health provider with any questions you may have regarding a medical condition. Never disregard professional medical advice because of something you have read on this platform.</p>
                </div>

                <div class="rr-legal-section" id="privacy">
                    <h2><i class="fa fa-lock"></i> 7. Privacy</h2>
                    <p>Your use of the Service is also governed by our <a href="{{ route('privacy') }}" style="color:var(--rr-primary);font-weight:600;">Privacy Policy</a>, which is incorporated into these Terms by reference. By using Rapid Rescue, you consent to the collection and use of your data as described in the Privacy Policy.</p>
                </div>

                <div class="rr-legal-section" id="liability">
                    <h2><i class="fa fa-scale-balanced"></i> 8. Limitation of Liability</h2>
                    <p>To the maximum extent permitted by law, Rapid Rescue and its operators shall not be liable for any indirect, incidental, special, or consequential damages arising out of your use of (or inability to use) the Service, including delays in emergency response caused by factors beyond our control such as traffic, weather, or network outages.</p>
                    <p>Our total liability for any claim shall not exceed the amount you paid us in the twelve months preceding the claim (if any).</p>
                </div>

                <div class="rr-legal-section" id="termination">
                    <h2><i class="fa fa-ban"></i> 9. Termination</h2>
                    <p>We may suspend or terminate your access to the Service at any time, with or without notice, for conduct that we determine violates these Terms, is harmful to other users or the platform, or for any other reason at our sole discretion.</p>
                    <p>You may delete your account at any time by contacting our support team. Upon termination, your right to use the Service will immediately cease.</p>
                </div>

                <div class="rr-legal-section" id="contact">
                    <h2><i class="fa fa-envelope"></i> 10. Contact Us</h2>
                    <p>If you have any questions about these Terms, please contact us:</p>
                    <ul>
                        <li><strong>Email:</strong> <span data-rr-ci="email">{{ $contactInfo->email ?? 'info@rapidrescue.com' }}</span></li>
                        <li><strong>Phone:</strong> 1122 (Emergency) / <span data-rr-ci="phone">{{ $contactInfo->phone ?? '+92 xxx xxxxxxx' }}</span> (Support)</li>
                        <li><strong>Address:</strong> <span data-rr-ci="address">{{ $contactInfo['address'] ?? 'XYZ Corporate Office, DHA Phase 6, Karachi, Pakistan' }}</span></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
@endsection
