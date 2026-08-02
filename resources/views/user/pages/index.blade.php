@extends('user.layouts.user')
@section('title', 'Rapid Rescue — 24/7 Ambulance Service')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/user/css/autocomplete.css') }}">
@endpush

@section('content')
    {{-- HERO --}}
    <section class="rr-hero" id="home">
        <div class="container">
            <div class="rr-hero-inner">
                <div>
                    <span class="rr-hero-badge"><span class="pulse-dot"></span> Live · Rapid 24/7 Dispatch</span>
                    <h1 class="rr-hero-title">When seconds count,<br><em>we're already moving.</em></h1>
                    <p class="rr-hero-lead">Rapid Rescue is a 24/7 ambulance service powered by GPS-tracked fleets and
                        certified paramedics. Request an emergency ride and we'll dispatch the nearest unit in real time.
                    </p>

                    <div class="rr-hero-ctas">
                        <a class="rr-btn rr-btn--primary">
                            <i class="fas fa-ambulance"></i> Request Now
                        </a>

                        <a data-scroll="process" href="javascript:void(0)" class="rr-btn rr-btn--ghost">
                            <i class="fas fa-play-circle"></i> How It Works
                        </a>
                    </div>

                    <div class="rr-hero-hotline">
                        <div class="rr-hero-hotline-icon"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <small>24/7 Emergency Hotline</small>
                            <strong>+92 2172959985</strong>
                        </div>
                    </div>
                </div>

                <div id="emergency-form">
                    <div class="rr-hero-card">
                        <div class="rr-hero-card-head">
                            <h3>Emergency Request</h3>
                            <span class="pill">Priority Dispatch</span>
                        </div>

                        <form id="bookingForm" enctype="multipart/form-data">
                            @csrf
                            <div class="rr-field">
                                <label class="rr-label">Hospital Name</label>
                                <div class="rr-autocomplete-wrap">
                                    <div class="rr-input-group">
                                        <i class="fas fa-hospital"></i>
                                        <input class="rr-input" type="text" placeholder="Search hospital…"
                                            id="hospital_name" name="hospital_name" maxlength="200" autocomplete="off">
                                    </div>
                                    <div class="rr-sug-box" id="hospitalSuggestions"></div>
                                    <input type="hidden" id="hospital_lat" name="hospital_lat">
                                    <input type="hidden" id="hospital_lng" name="hospital_lng">
                                    <small id="hospital_name_error" class="rr-field-error"></small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="rr-field">
                                        <label class="rr-label">Mobile Number</label>
                                        <div class="rr-input-group">
                                            <i class="fas fa-mobile-alt"></i>
                                            <input class="rr-input" type="tel" placeholder="03xx xxxxxxx" id="mobile_no"
                                                name="mobile_no" oninput="validatePakPhone(this)" value="">
                                        </div>
                                        <small id="phone_error" class="text-danger"></small>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="rr-field">
                                        <label class="rr-label">Email</label>
                                        <div class="rr-input-group">
                                            <i class="fas fa-envelope"></i>
                                            <input class="rr-input" type="email" placeholder="abc@gmail.com"
                                                id="emergency_email" name="emergency_email" maxlength="30" value=""
                                                required>
                                        </div>
                                        <small id="phone_error" class="text-danger"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="rr-field">
                                <label class="rr-label">Emergency Type</label>
                                <select name="type" id="type" class="rr-select">
                                    <option selected disabled value="0">Select type</option>
                                    <option value="1">Emergency</option>
                                    <option value="2">Non-Emergency</option>
                                </select>
                            </div>

                            <div class="rr-field">
                                <label class="rr-label">Pickup Location</label>
                                <div class="rr-autocomplete-wrap">
                                    <div class="rr-input-group">
                                        <i class="fas fa-location-arrow"></i>
                                        <input class="rr-input" type="text" name="pickup_address" id="pickup_address"
                                            placeholder="Search your pickup location…" maxlength="300" autocomplete="off">
                                    </div>
                                    <div class="rr-sug-box" id="pickupSuggestions"></div>
                                    <button type="button" class="rr-locate-btn mt-2" id="getLocation">
                                        <i class="fas fa-crosshairs"></i> Use my current location
                                    </button>
                                    <input type="hidden" id="latitude" name="latitude">
                                    <input type="hidden" id="longitude" name="longitude">
                                    <small id="pickup_address_error" class="rr-field-error"></small>
                                </div>
                            </div>

                            <div id="bookingMsg"
                                style="display:none;margin-bottom:10px;padding:10px 14px;border-radius:10px;font-size:0.85rem;font-weight:600;">
                            </div>
                            <button type="button" class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg"
                                id="dispatchBtn" onclick="submitEmergencyRequest()">
                                <i class="fas fa-ambulance"></i> Dispatch Ambulance
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- TRUST STRIP --}}
    <section class="rr-strip">
        <div class="container px-3">
            <div class="rr-trust-strip">
                <div class="row g-0">
                    <div class="col-md-6 col-lg-3">
                        <div class="rr-trust-item">
                            <div class="rr-trust-ic"><i class="fas fa-stopwatch"></i></div>
                            <div>
                                <h6>~ 8 min ETA</h6><small>Avg city response</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="rr-trust-item">
                            <div class="rr-trust-ic"><i class="fas fa-user-md"></i></div>
                            <div>
                                <h6>Certified Crews</h6><small>Trained paramedics</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="rr-trust-item">
                            <div class="rr-trust-ic"><i class="fas fa-shield-alt"></i></div>
                            <div>
                                <h6>Hospital-Grade Fleet</h6><small>Fully equipped units</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="rr-trust-item">
                            <div class="rr-trust-ic"><i class="fas fa-clock"></i></div>
                            <div>
                                <h6>24/7 Service</h6><small>Always available</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FEATURES --}}
    <section class="rr-features" id="features">
        <div class="container">
            <div class="rr-features-head section-head" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"><i class="fas fa-bolt"></i> Why Rapid Rescue</span>
                <h2>Built for the moments that <span>matter most</span></h2>
                <p>From the moment you tap dispatch, every second is engineered for speed, safety and peace of mind.</p>
            </div>

            <div class="row g-4 align-items-center">
                <div class="col-lg-4">
                    <div class="rr-features-col">
                        <div class="rr-feature-row" data-animate data-anim="fade-left" data-delay="100">
                            <div class="rr-feature-row__icon"><i class="fas fa-ambulance"></i></div>
                            <div>
                                <h5>Rapid Response</h5>
                                <p>Average dispatch under 90 seconds, with the closest ambulance auto-routed to your pickup.
                                </p>
                            </div>
                        </div>

                        <div class="rr-feature-row" data-animate data-anim="fade-left" data-delay="250">
                            <div class="rr-feature-row__icon"><i class="fas fa-clock"></i></div>
                            <div>
                                <h5>24/7 Availability</h5>
                                <p>Day, night, weekends, holidays — our control room never closes and always answers.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-none d-lg-block">
                    <div class="rr-features__img" data-animate data-anim="scale-in" data-delay="150">
                        <img src="{{ asset('assets/user/img/other/ambulance.png') }}" alt="Ambulance" class="img-fluid">
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="rr-features-col">
                        <div class="rr-feature-row is-right" data-animate data-anim="fade-right" data-delay="100">
                            <div class="rr-feature-row__icon"><i class="fas fa-cogs"></i></div>
                            <div>
                                <h5>State-of-the-Art Equipment</h5>
                                <p>Defibrillators, ventilators and trauma kits — every unit is hospital-grade equipped.</p>
                            </div>
                        </div>

                        <div class="rr-feature-row is-right" data-animate data-anim="fade-right" data-delay="250">
                            <div class="rr-feature-row__icon"><i class="fas fa-user-md"></i></div>
                            <div>
                                <h5>Expert Medical Team</h5>
                                <p>Certified paramedics and EMTs with continuous training in advanced life support.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- STATS --}}
    <section class="rr-stats" id="stats">
        <div class="container" style="position:relative;">
            <div class="row g-4 text-center">
                <div class="col-6 col-lg-3">
                    <div class="rr-stat" data-animate data-anim="scale-in" data-delay="0">
                        <div class="rr-stat__icon"><i class="fas fa-heartbeat"></i></div>
                        <div class="rr-stat__num"><span class="rr-counter" data-counter="1245">0</span>+</div>
                        <div class="rr-stat__label">Lives Saved</div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="rr-stat" data-animate data-anim="scale-in" data-delay="150">
                        <div class="rr-stat__icon"><i class="fas fa-ambulance"></i></div>
                        <div class="rr-stat__num"><span class="rr-counter" data-counter="78">0</span>+</div>
                        <div class="rr-stat__label">Ambulances Deployed</div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="rr-stat" data-animate data-anim="scale-in" data-delay="300">
                        <div class="rr-stat__icon"><i class="fas fa-users"></i></div>
                        <div class="rr-stat__num"><span class="rr-counter" data-counter="350">0</span>+</div>
                        <div class="rr-stat__label">Dedicated Team</div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="rr-stat" data-animate data-anim="scale-in" data-delay="450">
                        <div class="rr-stat__icon"><i class="fas fa-road"></i></div>
                        <div class="rr-stat__num"><span class="rr-counter" data-counter="15300">0</span>+</div>
                        <div class="rr-stat__label">Kilometers Traveled</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- SERVICES --}}
    <section class="rr-services" id="services">
        <div class="container">
            <div class="section-head" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"><i class="fas fa-briefcase-medical"></i> Services</span>
                <h2>Care for <span>every emergency</span></h2>
                <p>Six core services covering everything from your first call to your safe arrival at the right facility.
                </p>
            </div>

            <div class="row g-4" id="servicesGrid">
                @forelse($services as $s)
                    <div class="col-md-6 col-lg-4" data-animate data-anim="fade-up"
                        data-delay="{{ ($loop->index % 3) * 150 }}" data-service-id="{{ $s->id }}">
                        <div class="rr-card">
                            <div class="rr-card__icon"><i class="{{ $s->icon }}"></i></div>
                            <h4>{{ $s->title }}</h4>
                            <p>{{ $s->description }}</p>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-4" style="color:var(--rr-text-light)">No services listed yet.</div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- AMBULANCES --}}
    <section class="rr-ambulance" id="ambulances">
        <div class="container">
            <div class="section-head" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"><i class="fas fa-truck-medical"></i> Our Fleet</span>
                <h2>Ambulances <span>built to perform</span></h2>
                <p>Each unit in our fleet is maintained to the highest standards and equipped to handle any situation.</p>
            </div>

            {{-- Fleet Slider (4 visible · 1 card scroll) --}}
            <div class="rr-slider-wrap" id="fleetSlider" style="{{ $fleetAmbs->count() ? '' : 'display:none;' }}">
                <div class="rr-slider-track">
                    @foreach ($fleetAmbs as $a)
                        <div class="rr-slide" data-ambulance-id="{{ $a->id }}">
                            <div class="rr-fleet-card">
                                <div class="rr-fleet-card__img">
                                    @if ($a->card_image)
                                        <img src="{{ asset('assets/admin/img/fleet/' . $a->card_image) }}"
                                            alt="{{ $a->card_title ?: $a->vehicle_number }}">
                                    @else
                                        <img src="{{ asset('assets/admin/img/other/ambulance.png') }}"
                                            alt="{{ $a->vehicle_number }}">
                                    @endif
                                </div>

                                <div class="rr-fleet-card__body">
                                    <span class="rr-fleet-card__type">
                                        @if( $a->type == 1 )
                                            Basic Life Support
                                        @elseif( $a->type == 2 )
                                            Advanced Life Support
                                        @elseif( $a->type == 3 )
                                            Critical Care Transport
                                        @elseif( $a->type == 4 )
                                            Neonatal Transport
                                        @elseif( $a->type == 5 )
                                            Air Ambulance
                                        @endif
                                    </span>
                                    <h4>{{ $a->card_title ?: $a->vehicle_number }}</h4>
                                    <p>{{ $a->card_description ?: 'Fully equipped unit ready for any emergency.' }}</p>

                                    @if ($a->card_features)
                                        @php
                                            $features = json_decode($a->card_features, true);
                                            if (!is_array($features)) {
                                                $features = array_map('trim', explode(',', $a->card_features));
                                            }
                                            $features = array_slice(array_filter($features), 0, 4);
                                        @endphp
                                        @if (count($features))
                                            <div class="rr-fleet-card__badge">
                                                @foreach ($features as $feature)
                                                    <span>
                                                        <i class="fas fa-check-circle me-1" style="color:var(--rr-primary)"></i>
                                                        {{ $feature }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif

                                    @if ($a->card_rating)
                                        <div class="rr-fleet-card__rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @if ($i <= floor($a->card_rating))
                                                    <i class="fas fa-star"></i>
                                                @elseif($i - $a->card_rating < 1)
                                                    <i class="fas fa-star-half-alt"></i>
                                                @else
                                                    <i class="far fa-star"></i>
                                                @endif
                                            @endfor
                                            <span class="text-muted">{{ $a->card_rating }}{{ $a->card_trips ? ' · ' . $a->card_trips . ' trips' : '' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Fleet slider controls --}}
            <div class="rr-slider-controls" style="{{ $fleetAmbs->count() ? '' : 'display:none;' }}">
                <button class="rr-slider-nav prev" data-target="fleetSlider" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                <div class="rr-slider-dots" id="fleetDots"></div>
                <button class="rr-slider-nav next" data-target="fleetSlider" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
            </div>

            <div id="fleetEmpty" class="col-12 text-center py-4" style="color:var(--rr-text-light);{{ $fleetAmbs->count() ? 'display:none;' : '' }}">
                No Ambulances listed yet.
            </div>
        </div>
    </section>

    {{-- ABOUT --}}
    <section class="rr-about" id="about-us">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6" data-animate data-anim="fade-left" data-delay="100">
                    <div class="rr-about-images">
                        <div class="img-1">
                            <img src="{{ asset('assets/user/img/other/ambulance5.png') }}" alt="Emergency response"
                                class="img-fluid">
                        </div>

                        <div class="img-2">
                            <img src="{{ asset('assets/user/img/other/ambulance4.png') }}" alt="Ambulance fleet"
                                class="img-fluid">
                        </div>

                        <div class="rr-about-experience">
                            <strong>20+</strong>
                            <span>Years of trusted service</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-animate data-anim="fade-right" data-delay="200">
                    <span class="rr-eyebrow"><i class="fas fa-shield-heart"></i> About Us</span>
                    <h2 style="font-size:clamp(1.9rem,3vw,2.5rem);">Two decades of saving lives, <span
                            style="color:var(--rr-primary)">one second at a time.</span></h2>
                    <p class="text-muted" style="font-size:1.05rem; line-height:1.8;">At Rapid Rescue we exist for the
                        worst day of someone's life. Our mission is to ensure that help arrives faster, smarter and more
                        compassionate than anyone expected — backed by modern technology and a relentless team.</p>

                    <div class="row g-3 my-3">
                        <div class="col-6">
                            <div class="rr-pillar">
                                <img src="{{ asset('assets/user/img/other/about-icon-1.png') }}" alt="Vision">
                                <h5>Our Vision</h5>
                                <p>To be the gold standard for emergency response across the region.</p>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="rr-pillar">
                                <img src="{{ asset('assets/user/img/other/about-icon-2.png') }}" alt="Mission">
                                <h5>Our Mission</h5>
                                <p>Deliver world-class pre-hospital care with rapid, compassionate response.</p>
                            </div>
                        </div>
                    </div>

                    <ul class="rr-checklist">
                        <li><i class="fas fa-check"></i> Experienced Paramedic Team</li>
                        <li><i class="fas fa-check"></i> State-of-the-Art Equipment</li>
                        <li><i class="fas fa-check"></i> Rapid Response Times</li>
                        <li><i class="fas fa-check"></i> 24/7 Availability</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- PROCESS --}}
    <section class="rr-process" id="process">
        <div class="container">
            <div class="rr-process-head" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"
                    style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.14);color:#fff;">
                    <i class="fas fa-route"></i> Our Process
                </span>
                <h2>From request to rescue in <span>4 simple steps</span></h2>
                <p>A streamlined dispatch workflow that gets help moving the moment your request is submitted.</p>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="80">
                    <div class="rr-step">
                        <div class="rr-step__num">01</div>
                        <h4>Call Our Hotline</h4>
                        <p>Dial our 24/7 emergency number and describe the situation to our trained dispatcher.</p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="200">
                    <div class="rr-step">
                        <div class="rr-step__num">02</div>
                        <h4>Nearest Unit Matched</h4>
                        <p>The closest available ambulance and certified paramedic team are identified automatically.</p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="320">
                    <div class="rr-step">
                        <div class="rr-step__num">03</div>
                        <h4>Dispatch & Coordination</h4>
                        <p>Our control room coordinates the dispatch and notifies the destination hospital.</p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="440">
                    <div class="rr-step">
                        <div class="rr-step__num">04</div>
                        <h4>Receive Assistance</h4>
                        <p>Trained paramedics arrive, stabilise and safely transport you to care.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- REVIEWS --}}
    <section class="rr-reviews" id="testimonials">
        <div class="container">
            <div class="section-head" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"><i class="fas fa-star"></i> Testimonials</span>
                <h2>What our <span>patients say</span></h2>
                <p>Real stories from people whose lives were touched by Rapid Rescue's swift response.</p>
            </div>

            <div class="rr-slider-wrap" id="reviewsSlider" style="{{ $testimonials->count() ? '' : 'display:none;' }}">
                <div class="rr-slider-track">
                    @foreach ($testimonials as $t)
                        <div class="rr-slide" data-testimonial-id="{{ $t->id }}">
                            <div class="rr-review-card">
                                <div class="rr-review-stars">
                                    @for ($i = 1; $i <= $t->rating; $i++)
                                        <i class="fas fa-star"></i>
                                    @endfor
                                    @for ($i = $t->rating + 1; $i <= 5; $i++)
                                        <i class="far fa-star"></i>
                                    @endfor
                                </div>
                                <p class="rr-review-text">"{{ $t->content }}"</p>
                                <div class="rr-reviewer">
                                    <div class="rr-reviewer-avatar">{{ strtoupper(substr($t->name, 0, 1)) }}</div>
                                    <div>
                                        <div class="rr-reviewer-name">{{ $t->name }}</div>
                                        <div class="rr-reviewer-role">{{ $t->role }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Reviews slider controls --}}
            <div class="rr-slider-controls" style="{{ $testimonials->count() ? '' : 'display:none;' }}">
                <button class="rr-slider-nav prev" data-target="reviewsSlider" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                <div class="rr-slider-dots" id="reviewsDots"></div>
                <button class="rr-slider-nav next" data-target="reviewsSlider" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
            </div>

            <div id="reviewsEmpty" class="col-12 text-center py-4" style="color:var(--rr-text-light);{{ $testimonials->count() ? 'display:none;' : '' }}">
                No Reviews listed yet.
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="rr-faq" id="faq">
        <div class="container">
            <div class="section-head" style="margin-bottom:48px;" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"><i class="fas fa-circle-question"></i> FAQ</span>
                <h2>Frequently asked <span>questions</span></h2>
                <p>Everything you need to know about Rapid Rescue — answered clearly.</p>
            </div>

            <div class="rr-faq__grid">
                <div class="accordion rr-accordion" id="faqAccordion" data-animate data-anim="fade-left"
                    data-delay="150">
                    @forelse($faqs as $i => $faq)
                        <div class="accordion-item rr-accordion__item" data-faq-id="{{ $faq->id }}">
                            <h2 class="accordion-header" id="faqHead{{ $i }}">
                                <button class="accordion-button rr-accordion__btn {{ $i > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody{{ $i }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="faqBody{{ $i }}">
                                    <span class="rr-accordion__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="faqBody{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" aria-labelledby="faqHead{{ $i }}" data-bs-parent="#faqAccordion">
                                <div class="accordion-body rr-accordion__body">
                                    {{ $faq->answer }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="accordion-item rr-accordion__item">
                            <p style="padding:16px;color:var(--rr-text-light)">No FAQs available yet.</p>
                        </div>
                    @endforelse
                </div>

                <div class="rr-faq__cta" data-animate data-anim="fade-right" data-delay="250">
                    <div class="rr-faq__cta-inner">
                        <div class="rr-faq__cta-icon">
                            <i class="fa fa-headset"></i>
                        </div>

                        <h4>Still have questions?</h4>
                        <p>Our support team is available 24/7 to help you with anything you need.</p>

                        <a data-section="contact-form" href="/?s=contact-form" class="rr-btn rr-btn--primary">
                            <i class="fa fa-message"></i> Contact Support
                        </a>

                        <div class="rr-faq__hotline">
                            <i class="fa fa-phone-volume"></i>
                            <span data-rr-ci="phone">{{ $contactInfo['phone'] ?? '+92 2172959985' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CONTACT --}}
    <section class="rr-contact">
        <div class="container" id="contact-form">
            <div class="section-head" data-animate data-anim="fade-up">
                <span class="rr-eyebrow"><i class="fas fa-headset"></i> Contact Us</span>
                <h2>We're <span>always reachable</span></h2>
                <p>For urgent dispatch use the form above or call our hotline. For everything else, get in touch below.</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="80">
                    <div class="rr-contact-card">
                        <div class="rr-contact-card__icon"><i class="fas fa-map-marker-alt"></i></div>
                        <h5>Address</h5>
                        <p data-rr-ci="address">{{ $contactInfo['address'] ?? 'Faisal Cantonment Karachi, Pakistan' }}</p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="200">
                    <div class="rr-contact-card">
                        <div class="rr-contact-card__icon"><i class="fas fa-envelope"></i></div>
                        <h5>Email Us</h5>
                        <p data-rr-ci="phone">{{ $contactInfo['email'] ?? 'info@daniyal-khan.com' }}</p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="320">
                    <div class="rr-contact-card">
                        <div class="rr-contact-card__icon"><i class="fas fa-phone-alt"></i></div>
                        <h5>Telephone</h5>
                        <p data-rr-ci="email">{{ $contactInfo['phone'] ?? '+92 2172959985' }}</p>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3" data-animate data-anim="fade-up" data-delay="440">
                    <div class="rr-contact-card">
                        <div class="rr-contact-card__icon"><i class="fas fa-globe"></i></div>
                        <h5>Website</h5>
                        <p>rapid-rescue.daniyal-khan.com</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7" data-animate data-anim="fade-left" data-delay="150">
                    <div class="rr-form-card">
                        <h3>Send Your Message</h3>
                        <p>Tell us how we can help and we'll get back to you shortly.</p>

                        <form id="contactForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="rr-field">
                                        <label class="rr-label">Your Name</label>
                                        <input type="text" class="rr-input" id="contact_name" name="contact_name"
                                            maxlength="30" placeholder="Jane Doe" oninput="allowOnlyLetters(this)"
                                            value="">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="rr-field">
                                        <label class="rr-label">Your Email</label>
                                        <input type="email" class="rr-input" id="contact_email" name="contact_email"
                                            maxlength="30" placeholder="jane@example.com" value="">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="rr-field">
                                        <label class="rr-label">Phone</label>
                                        <input type="tel" class="rr-input" id="contact_phone" name="contact_phone"
                                            oninput="validatePakPhone(this)" placeholder="03xx xxxxxxx" value="">
                                        <small id="phone_error" class="text-danger field-error"></small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="rr-field">
                                        <label class="rr-label">Subject</label>
                                        <input type="text" class="rr-input" id="contact_subject"
                                            name="contact_subject" maxlength="50" placeholder="How can we help?"
                                            oninput="allowAlphaNumericCommaDot(this)">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="rr-field">
                                        <label class="rr-label">Message</label>
                                        <textarea class="rr-textarea" id="contact_message" name="contact_message" maxlength="500"
                                            placeholder="Write your message..." oninput="allowAlphaNumericCommaDot(this)"></textarea>
                                        <small id="contact_message_counter"
                                            style="color:var(--rr-text-light);font-size:0.78rem;display:block;text-align:right;margin-top:4px;">0/500</small>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="g-recaptcha-response" id="contact_captcha_response">

                            <div id="contact-recaptcha-widget" class="mt-3 mb-2 d-flex justify-content-center"></div>
                            <p id="contact-recaptcha-error" class="text-danger text-center"
                                style="display:none;font-size:0.84rem;margin-bottom:8px;">Please complete the human
                                verification.</p>

                            <button type="button" id="send-Btn"
                                class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg mt-2"
                                onclick="submitContactForm()">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5" data-animate data-anim="fade-right" data-delay="300">
                    <div class="rr-branches" id="branchesWrap">
                        @foreach ($branches as $branch)
                            <div class="rr-branch" data-branch-id="{{ $branch->id }}">
                                <h5><i class="fas fa-building"></i> {{ $branch->name }}</h5>
                                <div class="rr-branch__row">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Address:</span>
                                    {{ $branch->address }}
                                </div>
                                <div class="rr-branch__row">
                                    <i class="fas fa-phone-alt"></i>
                                    <span>Telephone:</span>
                                    {{ $branch->phone }}
                                </div>
                                @if ($branch->email)
                                <div class="rr-branch__row">
                                    <i class="fas fa-envelope"></i>
                                    <span>Email:</span>
                                    {{ $branch->email }}
                                </div>
                                @endif
                            </div>
                        @endforeach
                        <div class="rr-branch" style="text-align:center;color:var(--rr-text-light);{{ $branches->count() ? 'display:none;' : '' }}">
                            <p>No branch locations available.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit" async defer></script>
    <script src="{{ asset('assets/user/js/autocomplete.js') }}"></script>
    <script>
        window.routes = {
            contactSubmit: "{{ route('contactSubmit') }}",
            emergencyRequest: "{{ route('emergencyRequest') }}",
            trackingBase: "/tracking/",
            @auth('users')
                contactHistory: "{{ route('profile.grid') }}#contact-history",
            @endauth
        };
        window.recaptchaSiteKey = '{{ config("recaptcha.site_key") }}';
        var contactWidgetId;
        function onRecaptchaLoad() {
            contactWidgetId = grecaptcha.render('contact-recaptcha-widget', {
                sitekey: window.recaptchaSiteKey
            });
        }
    </script>
@endpush
