@extends('admin.layouts.admin')
@section('title', 'Users')
@section('page_title', 'Users')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Registered Users</h2>
            <p>View all registered user accounts and their profile details.</p>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <span class="status-pill status-1" style="font-size:0.8rem;">
                {{ $users->count() }} Total
            </span>
        </div>
    </div>

    <div class="adm-filter-row">
        <input type="text" class="form-control w-auto" id="searchUser" placeholder="Search name, username or email…" oninput="filterUserTable()">
        <select class="form-select w-auto" id="filterUserStatus" onchange="filterUserTable()">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="2">Suspended</option>
        </select>
        <select class="form-select w-auto" id="filterUserVerified" onchange="filterUserTable()">
            <option value="">All Verification</option>
            <option value="verified">Verified</option>
            <option value="unverified">Unverified</option>
        </select>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <div class="adm-empty">
                <i class="fa fa-users"></i>
                <p>No registered users found.</p>
            </div>
        @else
            <div class="pgd-scroll">
                <table class="table table-hover mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $d = $user->details;
                                $name = $d ? $d->first_name . ' ' . $d->last_name : '—';
                                $email = $d?->email ?? '—';
                                $phone = $d?->phone ?? '—';
                                $verified = $d?->email_verified_at;
                            @endphp
                            <tr class="pgd-row" data-id="{{ $user->id }}" data-status="{{ $user->status }}"
                                data-verified="{{ $verified ? 'verified' : 'unverified' }}"
                                data-search="{{ strtolower($name . ' ' . $user->username . ' ' . $email) }}">
                                <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="adm-icon-preview"
                                            style="width:32px;height:32px;border-radius:50%;font-size:0.8rem;overflow:hidden;padding:0;">
                                            @if ($d && $d->profile_picture && $d->profile_picture !== 'default.jpg')
                                                <img src="{{ asset('assets/user/img/users/' . $d->profile_picture) }}"
                                                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                                                    alt="">
                                            @else
                                                <div
                                                    style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fa fa-user" style="font-size:0.8rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <strong>{{ $name }}</strong>
                                    </div>
                                </td>
                                <td class="fs-xs" style="color:var(--adm-muted);">{{ $user->username }}</td>
                                <td class="fs-xs" style="color:var(--adm-muted);">{{ $email }}</td>
                                <td class="fs-xs" style="color:var(--adm-muted);">{{ $phone }}</td>
                                <td>
                                    @if ($user->status === '1')
                                        <span class="status-pill status-1">Active</span>
                                    @else
                                        <span class="status-pill status-4">Suspended</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($verified)
                                        <span class="status-pill status-1"><i class="fa fa-circle-check me-1" style="font-size:0.7rem;"></i>Verified</span>
                                    @else
                                        <span class="status-pill status-4"><i class="fa fa-circle-xmark me-1" style="font-size:0.7rem;"></i>Unverified</span>
                                    @endif
                                </td>
                                <td class="fs-xs" style="color:var(--adm-muted);">{{ $user->created_at?->format('d M Y') ?? 'N/A' }}
                                </td>
                                <td>
                                    <button class="btn-adm-icon btn-adm-icon--edit" title="View Details"
                                        onclick="viewUser({{ $user->id }})">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="userNoResults" style="display:none;">
                            <td colspan="9" class="text-center py-5" style="color:var(--adm-muted);">
                                <i class="fa fa-search d-block mb-2 opacity-50"></i>
                                No users match your search.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pgd-footer">
                <span class="pgd-info" id="usrInfo"></span>
                <div class="pgd-controls">
                    <button class="pgd-btn" id="usrPrev">&#8592; Prev</button>
                    <span class="pgd-pages" id="usrPages"></span>
                    <button class="pgd-btn" id="usrNext">Next &#8594;</button>
                </div>
            </div>
        @endif
    </div>

    {{-- View User Modal --}}
    <div class="modal fade" id="userViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="modal-title-icon"><i class="fa fa-user"></i></span>
                        User Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" id="userViewBody" style="min-height:200px;">
                    <div class="text-center py-5" id="userViewLoader">
                        <span class="spinner-border spinner-border-sm me-2"></span> Loading…
                    </div>
                    <div id="userViewContent" style="display:none;"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.usersRoutes = {
            show: "{{ url('admin/users/view') }}",
        };
        
        if (window.PGD) {
            PGD.init({
                id:      'usr',
                sel:     '#userTable tbody tr.pgd-row',
                prevId:  'usrPrev',
                nextId:  'usrNext',
                infoId:  'usrInfo',
                pagesId: 'usrPages',
                perPage: 10,
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/users.js') }}"></script>
@endpush
