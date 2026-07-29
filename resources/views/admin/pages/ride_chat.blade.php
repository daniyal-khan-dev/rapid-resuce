@extends('admin.layouts.admin')
@section('title', 'Ride Chat')
@section('page_title', 'Ride Chat')

@push('styles')
    <style>
        .msg-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .msg-item {
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--adm-border);
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }

        .msg-item:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        .msg-item.active {
            border-color: rgba(215, 44, 66, 0.5);
            background: rgba(215, 44, 66, 0.06);
        }

        .msg-item.unread {
            border-left: 3px solid #60a5fa;
        }

        .msg-badge-unread {
            display: inline-block;
            background: #3b82f6;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 20px;
            padding: 1px 7px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .chat-panel {
            display: flex;
            flex-direction: column;
            height: 620px;
            border: 1px solid var(--adm-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
            overflow: hidden;
        }

        .chat-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--adm-border);
            background: rgba(255, 255, 255, 0.04);
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--adm-muted);
            font-size: 0.9rem;
            flex-direction: column;
            gap: 12px;
        }

        .chat-footer {
            padding: 14px 16px;
            border-top: 1px solid var(--adm-border);
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .chat-footer textarea {
            flex: 1;
            resize: none;
            border-radius: 10px;
            min-height: 48px;
            max-height: 120px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--adm-border);
            color: #fff;
            padding: 10px 14px;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .chat-footer textarea:focus {
            outline: none;
            border-color: rgba(215, 44, 66, 0.4);
        }

        .chat-bubble {
            max-width: 78%;
            padding: 11px 16px;
            border-radius: 14px;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .bubble-admin {
            background: rgba(215, 44, 66, 0.15);
            color: #f8d0d5;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .bubble-user {
            background: rgba(255, 255, 255, 0.08);
            color: #e2e8f0;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .bubble-driver {
            background: rgba(16, 185, 129, 0.12);
            color: #6ee7b7;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .bubble-meta {
            font-size: 0.72rem;
            color: var(--adm-muted);
            margin-top: 3px;
        }

        .ride-status-badge,
        .ride-status-badge-1 {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 20px;
            padding: 1px 8px;
            vertical-align: middle;
        }

        .status-active  { background: rgba(34,197,94,0.18);  color: #86efac; border: 1px solid rgba(34,197,94,0.3); }
        .status-closed  { background: rgba(107,114,128,0.2); color: #9ca3af; border: 1px solid rgba(107,114,128,0.3); }
        .status-locked  { background: rgba(245,158,11,0.18); color: #fcd34d; border: 1px solid rgba(245,158,11,0.3); }

        .rc-search {
            margin-bottom: 0;
        }

        .rr-typing-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            animation: rrTypingBounce 1.2s infinite ease-in-out;
        }
        .rr-typing-dot:nth-child(1) { animation-delay: 0s; }
        .rr-typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .rr-typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes rrTypingBounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30%            { transform: translateY(-4px); opacity: 1; }
        }
    </style>
@endpush

@section('content')
    @php
        $statusLabels = [
            '1' => ['label' => 'Pending',     'chat' => 'locked'],
            '2' => ['label' => 'Accepted',     'chat' => 'active'],
            '3' => ['label' => 'En Route',     'chat' => 'active'],
            '4' => ['label' => 'Arrived',      'chat' => 'active'],
            '5' => ['label' => 'In Progress',  'chat' => 'active'],
            '6' => ['label' => 'Completed',    'chat' => 'closed'],
            '7' => ['label' => 'Cancelled',    'chat' => 'closed'],
            '8' => ['label' => 'Awaiting Acceptance',    'chat' => 'closed'],
        ];
    @endphp

    <div class="adm-page-header">
        <div>
            <h2>Ride Chat</h2>
            <p>View chat conversations for emergency bookings.</p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Conversation list --}}
        <div class="col-lg-4">
            <div class="card" style="max-height:640px;overflow-y:auto;">
                <div class="card-header py-3">
                    <input type="text" id="rcSearch" class="form-control form-control-sm rc-search"
                        placeholder="Search by booking ID or name…" oninput="rcFilter()">
                </div>
                <div class="card-body p-2">
                    <div id="rcEmptyState" class="text-center py-5 text-muted"
                        style="{{ $requests->count() ? 'display:none;' : '' }}">
                        <i class="fa fa-comments fa-2x d-block mb-3 opacity-25"></i>
                        <p class="mb-0">No ride chats yet.</p>
                    </div>

                    <div class="msg-list" id="rcList" style="{{ $requests->count() ? '' : 'display:none;' }}">
                        @foreach ($requests as $req)
                            @php
                                $lastMsg   = $req->rideChatMessages->first();
                                $chatState = $statusLabels[$req->status]['chat'] ?? 'locked';
                                $statusLabel = $statusLabels[$req->status]['label'] ?? 'Unknown';
                                $userName = trim( ($req->user?->details?->first_name ?? '') . ' ' . ($req->user?->details?->last_name ?? ''));
                            @endphp
                            <div class="msg-item {{ $req->unread_count > 0 ? 'unread' : '' }}"
                                id="rcItem{{ $req->id }}"
                                data-id="{{ $req->id }}"
                                data-search="{{ strtolower($req->rreb_id . ' ' . ($req->user?->name ?? '') . ' ' . ($req->driver?->name ?? '')) }}"
                                onclick="rcSelectConversation({{ $req->id }}, this)">

                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div style="min-width:0;">
                                        <div class="fw-bold text-white" style="font-size:0.82rem;">
                                            {{ $req->rreb_id }}
                                            @if ($req->unread_count > 0)
                                                <span class="msg-badge-unread">{{ $req->unread_count }}</span>
                                            @endif
                                        </div>
                                        <div class="text-truncate" style="font-size:0.76rem;color:var(--adm-muted);">
                                            <i class="fa fa-user" style="margin-right:3px;font-size:0.68rem;"></i>{{ $userName ?? '—' }}
                                            @if ($req->driver)
                                                &nbsp;&middot;&nbsp;<i class="fa fa-id-card" style="margin-right:3px;font-size:0.68rem;"></i>{{ $req->driver->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div style="flex-shrink:0;text-align:right;">
                                        <span class="ride-status-badge status-{{ $chatState }}">{{ $statusLabel }}</span>
                                        @if ($lastMsg)
                                            <div style="font-size:0.68rem;color:var(--adm-muted);margin-top:3px;">
                                                {{ $lastMsg->created_at->format('d M') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if ($lastMsg)
                                    <div class="mt-1 text-truncate" style="font-size:0.78rem;color:rgba(255,255,255,0.4);">
                                        <span style="color:var(--adm-muted);font-size:0.7rem;">{{ ucfirst($lastMsg->sender_type) }}:</span>
                                        {{ Str::limit($lastMsg->message, 48) }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div id="rcNoResults" class="text-center py-4 text-muted" style="display:none;font-size:0.85rem;">
                        <i class="fa fa-search d-block mb-2 opacity-50"></i>No conversations match your search.
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat panel --}}
        <div class="col-lg-8">
            <div class="chat-panel">
                <div class="chat-header" id="rcChatHeader">
                    <div class="chat-placeholder" style="height:auto;padding:0;">
                        <span style="color:var(--adm-muted);font-size:0.88rem;">
                            <i class="fa fa-arrow-left me-2"></i>Select a conversation to view messages
                        </span>
                    </div>
                </div>

                <div class="chat-body" id="rcChatBody">
                    <div class="chat-placeholder">
                        <i class="fa fa-comments fa-2x opacity-25"></i>
                        <span>Select a booking from the left to view the chat</span>
                    </div>
                </div>

                <div class="chat-footer" id="rcChatFooter" style="display:none;">
                    <div style="display:flex;gap:8px;">
                        <textarea id="rcReplyInput" placeholder="Type your reply…" rows="1"></textarea>
                        <button class="btn btn-danger px-3" id="rcSendBtn" title="Send reply">
                            <i class="fa fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const rcRoute = "{{ url('/admin/ride-chat') }}";
    </script>
    <script src="{{ asset('assets/admin/js/rideChat.js') }}"></script>
    <script src="{{ asset('assets/admin/js/rideChatRealtime.js') }}"></script>
@endpush
