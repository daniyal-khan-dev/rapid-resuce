@extends('user.layouts.user')
@section('title', 'Privacy Policy | Rapid Rescue')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/user/css/pptc.css') }}">
@endpush

@section('content')
    {{-- Hero --}}
    <section class="rr-legal-hero">
        <div class="container text-center">
            <div class="rr-legal-hero__badge">
                <i class="fa fa-shield-halved"></i> Legal Document
            </div>
            <h1>Privacy Policy</h1>
            <p>How Rapid Rescue collects, uses, and protects your personal information.</p>
        </div>
    </section>

    {{-- Body --}}
    <div class="container py-5 px-3 px-md-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-9">
                <div class="rr-legal-updated">
                    <i class="fa fa-calendar-check"></i>
                    Last updated: {{ date('d F Y') }} &nbsp;|&nbsp; Applies to all Rapid Rescue users and services.
                </div>

                <div class="rr-legal-toc">
                    <h6><i class="fa fa-list"></i> Table of Contents</h6>
                    <ol>
                        <li><a href="#overview">Overview</a></li>
                        <li><a href="#data-collected">Data We Collect</a></li>
                        <li><a href="#how-used">How We Use Your Data</a></li>
                        <li><a href="#sharing">Data Sharing</a></li>
                        <li><a href="#medical-data">Medical Data</a></li>
                        <li><a href="#retention">Data Retention</a></li>
                        <li><a href="#security">Security</a></li>
                        <li><a href="#rights">Your Rights</a></li>
                        <li><a href="#children">Children's Privacy</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                    </ol>
                </div>

                <div class="rr-legal-section" id="overview">
                    <h2><i class="fa fa-circle-info"></i> 1. Overview</h2>
                    <p>Rapid Rescue ("we", "us", "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform and related services.</p>
                    <p>By creating an account and using Rapid Rescue, you consent to the data practices described in this policy. If you do not agree, please discontinue use of the Service.</p>
                </div>

                <div class="rr-legal-section" id="data-collected">
                    <h2><i class="fa fa-database"></i> 2. Data We Collect</h2>
                    <p>We collect the following categories of information:</p>
                    <div class="table-responsive">
                        <table class="rr-data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Examples</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Account Info</strong></td>
                                    <td>Name, username, email, phone, profile picture</td>
                                    <td>Account creation &amp; identification</td>
                                </tr>
                                <tr>
                                    <td><strong>Medical Info</strong></td>
                                    <td>Blood type, allergies, conditions, emergency contact</td>
                                    <td>Medical ID card &amp; paramedic access</td>
                                </tr>
                                <tr>
                                    <td><strong>Location Data</strong></td>
                                    <td>GPS coordinates at time of emergency request</td>
                                    <td>Dispatching the nearest ambulance</td>
                                </tr>
                                <tr>
                                    <td><strong>Usage Data</strong></td>
                                    <td>Request history, login timestamps, device info</td>
                                    <td>Service improvement &amp; security</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rr-legal-section" id="how-used">
                    <h2><i class="fa fa-gears"></i> 3. How We Use Your Data</h2>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Operate and maintain the Rapid Rescue platform.</li>
                        <li>Dispatch ambulances to your location during emergencies.</li>
                        <li>Provide your Medical ID card to paramedics when requested.</li>
                        <li>Send notifications about your emergency request status.</li>
                        <li>Improve the Service through analytics and feedback.</li>
                        <li>Comply with legal obligations and prevent fraud.</li>
                    </ul>
                    <p>We do <strong>not</strong> use your data for advertising or sell it to third-party marketers.</p>
                </div>

                <div class="rr-legal-section" id="sharing">
                    <h2><i class="fa fa-share-nodes"></i> 4. Data Sharing</h2>
                    <p>We may share your information only in the following circumstances:</p>
                    <ul>
                        <li><strong>Emergency Responders:</strong> Paramedics and dispatchers receive relevant data (location, Medical ID) during an active emergency request.</li>
                        <li><strong>Service Providers:</strong> Trusted vendors who assist in operating the platform (e.g., hosting, SMS gateways) under strict confidentiality agreements.</li>
                        <li><strong>Legal Obligations:</strong> When required by law, regulation, court order, or government authority.</li>
                        <li><strong>Business Transfers:</strong> In the event of a merger or acquisition, your data may be transferred as part of that transaction.</li>
                    </ul>
                    <p>We never sell your personal data to third parties.</p>
                </div>

                <div class="rr-legal-section" id="medical-data">
                    <h2><i class="fa fa-heart-pulse"></i> 5. Medical Data</h2>
                    <p>Medical information you provide (blood type, allergies, conditions, emergency contact) is treated with the highest level of sensitivity. This data is:</p>
                    <ul>
                        <li>Stored with encryption at rest.</li>
                        <li>Accessible only to paramedics and dispatchers during an active emergency.</li>
                        <li>Never shared with insurance companies or third-party advertisers.</li>
                        <li>Deletable upon your request.</li>
                    </ul>
                    <p>You may update or remove your Medical ID data at any time from your profile.</p>
                </div>

                <div class="rr-legal-section" id="retention">
                    <h2><i class="fa fa-clock-rotate-left"></i> 6. Data Retention</h2>
                    <p>We retain your personal data for as long as your account is active or as needed to provide services. If you delete your account:</p>
                    <ul>
                        <li>Personal information is removed within 30 days.</li>
                        <li>Emergency request logs may be retained for up to 12 months for regulatory compliance.</li>
                        <li>Anonymised, aggregate data (no personally identifiable information) may be retained indefinitely for service analytics.</li>
                    </ul>
                </div>

                <div class="rr-legal-section" id="security">
                    <h2><i class="fa fa-lock"></i> 7. Security</h2>
                    <p>We implement industry-standard security measures to protect your data, including:</p>
                    <ul>
                        <li>Bcrypt hashing for all stored passwords — we never store plain-text passwords.</li>
                        <li>Encrypted HTTPS connections for all data in transit.</li>
                        <li>Session-based authentication with CSRF protection.</li>
                        <li>Regular security audits and dependency updates.</li>
                    </ul>
                    <p>No method of transmission over the Internet is 100% secure, and we cannot guarantee absolute security. In the event of a breach, we will notify affected users as required by law.</p>
                </div>

                <div class="rr-legal-section" id="rights">
                    <h2><i class="fa fa-user-check"></i> 8. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li><strong>Access</strong> — Request a copy of the personal data we hold about you.</li>
                        <li><strong>Correction</strong> — Update inaccurate or incomplete data via your profile.</li>
                        <li><strong>Deletion</strong> — Request deletion of your account and personal data.</li>
                        <li><strong>Portability</strong> — Request your data in a machine-readable format.</li>
                        <li><strong>Objection</strong> — Object to processing where we rely on legitimate interest.</li>
                    </ul>
                    <p>To exercise any of these rights, contact us at <a href="mailto:{{ $contactInfo->email ?? 'info@rapidrescue.com' }}" data-rr-ci="email-link" style="color:var(--rr-primary);font-weight:600;">{{ $contactInfo->email ?? 'info@rapidrescue.com' }}</a>.</p>
                </div>

                <div class="rr-legal-section" id="children">
                    <h2><i class="fa fa-child"></i> 9. Children's Privacy</h2>
                    <p>Rapid Rescue is not directed at children under the age of 13. We do not knowingly collect personal information from children under 13. If we discover that a child under 13 has provided personal information, we will delete it immediately.</p>
                    <p>If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.</p>
                </div>

                <div class="rr-legal-section" id="contact">
                    <h2><i class="fa fa-envelope"></i> 10. Contact Us</h2>
                    <p>If you have any questions, concerns, or requests regarding this Privacy Policy, please reach out:</p>
                    <ul>
                        <li><strong>Email:</strong> <span data-rr-ci="email">{{ $contactInfo->email ?? 'info@rapidrescue.com' }}</span></li>
                        <li><strong>Phone:</strong> <span data-rr-ci="phone">{{ $contactInfo->phone ?? '+92 xxx xxxxxxx' }}</span> (Mon – Fri, 9 AM – 6 PM)</li>
                        <li><strong>Address:</strong> <span data-rr-ci="address">{{ $contactInfo['address'] ?? 'XYZ Corporate Office, DHA Phase 6, Karachi, Pakistan' }}</span></li>
                    </ul>
                    <p>We will respond to all legitimate requests within 30 days.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
