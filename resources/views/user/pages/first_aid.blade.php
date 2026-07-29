@extends('user.layouts.user')
@section('title', 'First Aid Instructions — Rapid Rescue')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/user/css/first_aid.css') }}">
@endpush

@section('content')
    {{-- Hero --}}
    <div class="rr-fa-hero">
        <div class="container">
            <span class="rr-eyebrow"><i class="fas fa-first-aid"></i> First Aid Guide</span>
            <h1>While You Wait for Help</h1>
            <p>These step-by-step guides can be life-saving in the minutes before our paramedics arrive. Stay calm and follow each step carefully.</p>
        </div>
    </div>

    <section class="rr-fa-section">
        <div class="container">
            <div class="section-head text-center">
                <span class="rr-eyebrow"><i class="fas fa-bolt"></i> Emergency Guides</span>
                <h2>Common Emergency <span>First Aid</span> Procedures</h2>
                <p class="text-muted">Quick reference guides for the most common medical emergencies.</p>
            </div>

            <div class="row g-4 mb-5">
                {{-- CPR --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--red"><i class="fas fa-heartbeat"></i></div>
                        <h4>CPR (Cardiopulmonary Resuscitation)</h4>
                        <p class="lead">For unresponsive persons who are not breathing normally.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Call for emergency help immediately.</li>
                            <li><span class="step-num">2</span>Place the person on a firm, flat surface.</li>
                            <li><span class="step-num">3</span>Place heel of hand on centre of chest.</li>
                            <li><span class="step-num">4</span>Push down hard and fast — 30 compressions at 100–120/min.
                            </li>
                            <li><span class="step-num">5</span>Give 2 rescue breaths (if trained).</li>
                            <li><span class="step-num">6</span>Continue until help arrives or person recovers.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> Do NOT stop unless exhausted or a defibrillator (AED) is available.</div>
                    </div>
                </div>

                {{-- Choking --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--orange"><i class="fas fa-wind"></i></div>
                        <h4>Choking</h4>
                        <p class="lead">For someone who cannot breathe, cough, or speak due to airway obstruction.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Ask "Are you choking?" — if yes, act immediately.</li>
                            <li><span class="step-num">2</span>Lean person forward and give 5 firm back blows between shoulder blades.</li>
                            <li><span class="step-num">3</span>Perform 5 abdominal thrusts (Heimlich manoeuvre).</li>
                            <li><span class="step-num">4</span>Alternate back blows and abdominal thrusts.</li>
                            <li><span class="step-num">5</span>If unconscious, begin CPR and call for help.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> For infants, use 5 back blows and 5 chest thrusts only.</div>
                    </div>
                </div>

                {{-- Severe Bleeding --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--red"><i class="fas fa-tint"></i></div>
                        <h4>Severe Bleeding</h4>
                        <p class="lead">For deep cuts or wounds with heavy blood loss.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Put on gloves if available. Keep yourself safe.</li>
                            <li><span class="step-num">2</span>Apply firm direct pressure using a clean cloth or bandage.</li>
                            <li><span class="step-num">3</span>Do NOT remove the cloth — add more on top if needed.</li>
                            <li><span class="step-num">4</span>Elevate the injured area above heart level if possible.</li>
                            <li><span class="step-num">5</span>Maintain pressure until emergency services arrive.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> Never use a tourniquet unless trained and bleeding is life-threatening.</div>
                    </div>
                </div>

                {{-- Burns --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--orange"><i class="fas fa-fire"></i></div>
                        <h4>Burns</h4>
                        <p class="lead">For thermal burns from heat, steam, or flames.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Remove from the source of heat immediately.</li>
                            <li><span class="step-num">2</span>Cool the burn under cool (not cold) running water for 10–20 minutes.</li>
                            <li><span class="step-num">3</span>Do NOT use ice, butter, or toothpaste.</li>
                            <li><span class="step-num">4</span>Remove jewellery/clothing near the burn area.</li>
                            <li><span class="step-num">5</span>Cover loosely with a clean non-fluffy material.</li>
                            <li><span class="step-num">6</span>Seek medical attention for large or deep burns.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> For chemical burns, rinse with water and call poison control.</div>
                    </div>
                </div>

                {{-- Fracture / Suspected Broken Bone --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--blue"><i class="fas fa-bone"></i></div>
                        <h4>Fractures / Broken Bones</h4>
                        <p class="lead">For suspected broken limbs or bones after trauma.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Keep the person still — do NOT move them unnecessarily.</li>
                            <li><span class="step-num">2</span>Immobilise the injured area with a splint or firm padding.</li>
                            <li><span class="step-num">3</span>Apply ice pack wrapped in cloth to reduce swelling.</li>
                            <li><span class="step-num">4</span>Elevate the injured limb if possible.</li>
                            <li><span class="step-num">5</span>Do NOT try to straighten the bone.</li>
                            <li><span class="step-num">6</span>Wait for emergency medical assistance.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> Suspect spinal injury if back/neck trauma occurred — do NOT move.</div>
                    </div>
                </div>

                {{-- Stroke --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--purple"><i class="fas fa-brain"></i></div>
                        <h4>Stroke (FAST Method)</h4>
                        <p class="lead">Recognise and act on stroke symptoms immediately.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">F</span><strong>Face</strong> — Is one side drooping? Ask them to smile.</li>
                            <li><span class="step-num">A</span><strong>Arms</strong> — Can they raise both arms? Is one weak?</li>
                            <li><span class="step-num">S</span><strong>Speech</strong> — Is speech slurred or confused?</li>
                            <li><span class="step-num">T</span><strong>Time</strong> — Call emergency immediately if any sign is present.</li>
                            <li><span class="step-num">5</span>Keep them comfortable and still. Do NOT give food/water.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> Every minute counts in a stroke. Time = Brain cells.</div>
                    </div>
                </div>

                {{-- Heart Attack --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--red"><i class="fas fa-heart-pulse"></i></div>
                        <h4>Heart Attack</h4>
                        <p class="lead">For chest pain, tightness, or suspected cardiac event.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Call for emergency help immediately.</li>
                            <li><span class="step-num">2</span>Have the person sit or lie in a comfortable position.</li>
                            <li><span class="step-num">3</span>Loosen tight clothing around neck, chest, or waist.</li>
                            <li><span class="step-num">4</span>Give aspirin (300mg) if they are not allergic.</li>
                            <li><span class="step-num">5</span>Reassure and calm the person — anxiety worsens the attack.</li>
                            <li><span class="step-num">6</span>Begin CPR if they become unconscious and stop breathing.</li>
                        </ul>
                    </div>
                </div>

                {{-- Drowning / Unconscious --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--teal"><i class="fas fa-person-drowning"></i></div>
                        <h4>Drowning / Unconscious Person</h4>
                        <p class="lead">For someone unconscious or pulled from water.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Remove from water only if safe to do so.</li>
                            <li><span class="step-num">2</span>Check for breathing — tilt head back, lift chin.</li>
                            <li><span class="step-num">3</span>If not breathing, begin CPR immediately (30:2 ratio).</li>
                            <li><span class="step-num">4</span>Place in recovery position if breathing returns.</li>
                            <li><span class="step-num">5</span>Keep them warm — drowning victims lose heat rapidly.</li>
                            <li><span class="step-num">6</span>Do NOT leave alone until help arrives.</li>
                        </ul>
                    </div>
                </div>

                {{-- Seizures --}}
                <div class="col-md-6 col-lg-4">
                    <div class="rr-fa-card">
                        <div class="rr-fa-icon rr-fa-icon--green"><i class="fas fa-bolt"></i></div>
                        <h4>Seizures / Convulsions</h4>
                        <p class="lead">For uncontrolled shaking or epileptic episodes.</p>
                        <ul class="rr-fa-steps">
                            <li><span class="step-num">1</span>Keep calm and time the seizure.</li>
                            <li><span class="step-num">2</span>Clear the area of hard or sharp objects.</li>
                            <li><span class="step-num">3</span>Do NOT restrain the person — let the seizure happen.</li>
                            <li><span class="step-num">4</span>Place something soft under their head.</li>
                            <li><span class="step-num">5</span>After it stops, place them in the recovery position.</li>
                            <li><span class="step-num">6</span>Call for help if it lasts more than 5 minutes.</li>
                        </ul>
                        <div class="rr-fa-warning"><i class="fas fa-exclamation-triangle"></i> NEVER put anything in the person's mouth during a seizure.</div>
                    </div>
                </div>
            </div>

            {{-- Emergency hotline CTA --}}
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="rr-fa-hotline">
                        <h4 style="color: white;">Is this a life-threatening emergency?</h4>
                        <div class="phone" data-rr-ci="phone">{{ $contactInfo->phone ?? '+92 xxx xxxxxxx' }}</div>
                        <p>Our 24/7 emergency hotline connects you directly to our dispatch centre. Help will be on its way within minutes.</p>
                        <a href="{{ route('home') }}#emergency-form" class="rr-btn rr-btn--ghost mt-3" style="display:inline-flex;align-items:center;gap:8px;color:#fff;border-color:rgba(255,255,255,0.4)">
                            <i class="fas fa-ambulance"></i> Request Ambulance Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
