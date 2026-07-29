@extends('driver.layouts.driver')
@section('title', 'My Profile')
@section('page_title', 'My Profile')

@section('content')

    <div class="dri-page-header">
        <div>
            <h2>My Profile</h2>
            <p>View and update your account information.</p>
        </div>
        @php
            $statusLabels = ['1' => 'Online', '2' => 'Offline', '3' => 'Busy'];
            $statusClass  = ['1' => 'online',  '2' => 'offline',  '3' => 'busy'];
        @endphp
        <span class="dri-profile-status {{ $statusClass[$driver->status] ?? 'offline' }}">
            <span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span>
            {{ $statusLabels[$driver->status] ?? 'Unknown' }}
        </span>
    </div>

    <div class="dri-profile-grid">
        {{-- left: Side cards --}}
        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- Profile --}}
            <div class="dri-profile-card">
                <div class="dri-profile-avatar">
                    <i class="fa fa-id-card"></i>
                </div>
                <div class="dri-profile-name">{{ $driver->name }}</div>
                <div class="dri-profile-role">Driver</div>
    
                <div class="dri-profile-meta">
                    @if($driver->username)
                    <div class="dri-profile-meta-row">
                        <i class="fa fa-at"></i>
                        <span>{{ $driver->username }}</span>
                    </div>
                    @endif
                    @if($driver->email)
                    <div class="dri-profile-meta-row">
                        <i class="fa fa-envelope"></i>
                        <span>{{ $driver->email }}</span>
                    </div>
                    @endif
                    @if($driver->phone)
                    <div class="dri-profile-meta-row">
                        <i class="fa fa-phone"></i>
                        <span>{{ $driver->phone }}</span>
                    </div>
                    @endif
                    @if($driver->license_no)
                    <div class="dri-profile-meta-row">
                        <i class="fa fa-id-badge"></i>
                        <span>License: {{ $driver->license_no }}</span>
                    </div>
                    @endif
                    <div class="dri-profile-meta-row">
                        <i class="fa fa-calendar"></i>
                        <span>Joined {{ $driver->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Read-only Info --}}
            <div class="dri-form-section">
                <div class="dri-form-section-hd">
                    <div class="dri-form-section-hd__icon blue" style="background:rgba(139,92,246,.15);color:#c4b5fd;">
                        <i class="fa fa-circle-info"></i>
                    </div>
                    <div>
                        <h3>Account Information</h3>
                        <h4>Details managed by your administrator.</h4>
                    </div>
                </div>
                <div class="dri-form-body">
                    <div class="dri-form-row">
                        <div class="dri-form-group">
                            <label>Account Status</label>
                            <div style="padding-top:4px;">
                                <span class="dri-profile-status {{ $statusClass[$driver->status] ?? 'offline' }}" style="margin:0;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                                    {{ $statusLabels[$driver->status] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                        <div class="dri-form-group">
                            <label>Member Since</label>
                            <input type="text" value="{{ $driver->created_at->format('d M Y') }}" class="dri-form-input" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Edit forms --}}
        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- Edit Profile --}}
            <div class="dri-form-section">
                <div class="dri-form-section-hd">
                    <div class="dri-form-section-hd__icon blue"><i class="fa fa-user-pen"></i></div>
                    <div>
                        <h3>Edit Profile</h3>
                        <h4>Update your name and contact number.</h4>
                    </div>
                </div>
                <form method="POST" action="{{ route('driver.profile.update') }}">
                    @csrf
                    <div class="dri-form-body">
                        <div class="dri-form-row">
                            <div class="dri-form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $driver->name) }}"
                                       class="dri-form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                       placeholder="Your full name" required>
                                @error('name') <span class="dri-field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="dri-form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $driver->phone) }}"
                                       class="dri-form-input"
                                       placeholder="e.g. 03xx xxxxxxx">
                            </div>
                        </div>
                        <div class="dri-form-row">
                            <div class="dri-form-group">
                                <label>Username</label>
                                <input type="text" value="{{ $driver->username }}" class="dri-form-input" readonly>
                            </div>
                            <div class="dri-form-group">
                                <label>Email Address</label>
                                <input type="email" value="{{ $driver->email }}" class="dri-form-input" readonly>
                            </div>
                        </div>
                        <div class="dri-form-row">
                            <div class="dri-form-group">
                                <label>License Number</label>
                                <input type="text" value="{{ $driver->license_no ?? '—' }}" class="dri-form-input" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="dri-form-footer">
                        <button type="submit" class="btn-dri-primary">
                            <i class="fa fa-floppy-disk"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="dri-form-section">
                <div class="dri-form-section-hd">
                    <div class="dri-form-section-hd__icon amber"><i class="fa fa-lock"></i></div>
                    <div>
                        <h3>Change Password</h3>
                        <h4>Choose a strong password with at least 8 characters.</h4>
                    </div>
                </div>
                <form method="POST" action="{{ route('driver.profile.password') }}" id="pwdForm">
                    @csrf
                    <div class="dri-form-body">
                        <div class="dri-form-row">
                            <div class="dri-form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password"
                                       class="dri-form-input {{ $errors->has('current_password') ? 'is-invalid' : '' }}"
                                       placeholder="Enter current password" required>
                                @error('current_password') <span class="dri-field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="dri-form-row">
                            <div class="dri-form-group">
                                <label>New Password</label>
                                <input type="password" name="password"
                                       class="dri-form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                       placeholder="Min. 8 characters" required minlength="8">
                                @error('password') <span class="dri-field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="dri-form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="password_confirmation"
                                       class="dri-form-input"
                                       placeholder="Repeat new password" required minlength="8">
                            </div>
                        </div>
                    </div>
                    <div class="dri-form-footer">
                        <button type="submit" class="btn-dri-primary">
                            <i class="fa fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
