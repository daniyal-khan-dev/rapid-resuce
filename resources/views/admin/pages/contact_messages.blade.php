@extends('admin.layouts.admin')
@section('title', 'Contact Messages')
@section('page_title', 'Contact Messages')

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
            border-left: 3px solid #f87184;
        }

        .msg-badge-unread {
            display: inline-block;
            background: #f87184;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 20px;
            padding: 1px 7px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .msg-badge-reply {
            display: inline-block;
            background: #f59e0b;
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

        .typing-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--adm-muted);
            margin: 0 1px;
            animation: typingBounce 1.2s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: .2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes typingBounce {

            0%,
            80%,
            100% {
                transform: translateY(0);
                opacity: .4
            }

            40% {
                transform: translateY(-4px);
                opacity: 1
            }
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

        .bubble-meta {
            font-size: 0.72rem;
            color: var(--adm-muted);
            margin-top: 3px;
        }

        .bubble-original {
            padding: 11px 16px;
            border-radius: 10px;
            background: rgba(59, 130, 246, 0.10);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            font-size: 0.88rem;
            line-height: 1.5;
            margin-bottom: 6px;
        }

        .resolve-btn {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1.5px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }

        .resolve-btn:hover {
            background: rgba(34, 197, 94, 0.15);
        }

        .resolve-btn.resolved {
            border-color: rgba(107, 114, 128, 0.4);
            color: #9ca3af;
        }

        .msg-badge-guest {
            display: inline-block;
            background: rgba(99, 102, 241, 0.25);
            color: #a5b4fc;
            font-size: 0.63rem;
            font-weight: 700;
            border-radius: 20px;
            padding: 1px 7px;
            margin-left: 5px;
            vertical-align: middle;
            border: 1px solid rgba(99, 102, 241, 0.35);
        }

        .msg-filter-btn {
            flex: 1;
            padding: 4px 0;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1.5px solid var(--adm-border);
            color: var(--adm-muted);
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }

        .msg-filter-btn:hover {
            border-color: rgba(215, 44, 66, 0.4);
            color: #fff;
        }

        .msg-filter-btn.active {
            border-color: rgba(215, 44, 66, 0.6);
            color: #fff;
            background: rgba(215, 44, 66, 0.15);
        }

        .guest-email-notice {
            display: none;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            color: #a5b4fc;
            padding: 0 2px 8px;
            background: rgba(99, 102, 241, 0.08);
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 8px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
    </style>
@endpush

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Contact Messages</h2>
            <p>
                View and reply to messages from visitors and users. Guest replies are delivered via email.
                @if ($unread > 0)
                    <span class="msg-badge-unread">{{ $unread }} unread</span>
                @endif
            </p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Message list --}}
        <div class="col-lg-4">
            <div class="card" style="max-height:640px;overflow-y:auto;">
                <div class="card-header py-3 d-flex flex-column gap-2">
                    <input type="text" id="msgSearch" class="form-control form-control-sm" placeholder="Search messages…" oninput="filterMessages()">
                    <div class="d-flex gap-2">
                        <button class="msg-filter-btn active" id="filterAll">All</button>
                        <button class="msg-filter-btn" id="filterUser"><i class="fa fa-user me-1"></i>Users</button>
                        <button class="msg-filter-btn" id="filterGuest"><i class="fa fa-user-slash me-1"></i>Guests</button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div id="msgEmptyState" class="text-center py-5 text-muted" style="{{ $messages->count() ? 'display:none;' : '' }}">
                        <i class="fa fa-inbox fa-2x d-block mb-3 opacity-25"></i>
                        <p class="mb-0">No messages yet.</p>
                    </div>
                    
                    <div class="msg-list" id="msgList" style="{{ $messages->count() ? '' : 'display:none;' }}">
                        @foreach ($messages as $msg)
                            @php
                                $hasUnreadReply =
                                    $msg->admin_read &&
                                    $msg->replies->where('sender_type', 'user')->where('is_read', false)->isNotEmpty();
                                $itemUnread = !$msg->admin_read || $hasUnreadReply;
                            @endphp
                            <div class="msg-item {{ $itemUnread ? 'unread' : '' }}" id="msgItem{{ $msg->id }}"
                                data-search="{{ strtolower($msg->name . ' ' . $msg->subject . ' ' . $msg->email) }}"
                                data-type="{{ $msg->user_id ? 'user' : 'guest' }}">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div style="min-width:0;">
                                        <div class="fw-bold text-white fs-xs text-truncate">
                                            {{ $msg->name }}
                                            @if (!$msg->admin_read)
                                                <span class="msg-badge-unread">new</span>
                                            @elseif ($hasUnreadReply)
                                                <span class="msg-badge-unread msg-badge-reply">Reply</span>
                                            @endif
                                            @if (!$msg->user_id)
                                                <span class="msg-badge-guest"
                                                    title="Submitted without an account — replies go via email">Guest</span>
                                            @endif
                                        </div>
                                        <div class="text-truncate" style="font-size:0.78rem;color:var(--adm-muted);">
                                            {{ $msg->subject }}</div>
                                    </div>

                                    <div style="font-size:0.7rem;color:var(--adm-muted);white-space:nowrap;flex-shrink:0;">
                                        {{ $msg->created_at->format('d M') }}
                                    </div>
                                </div>

                                <div class="mt-1 text-truncate" style="font-size:0.78rem;color:rgba(255,255,255,0.4);">
                                    {{ Str::limit($msg->message, 55) }}
                                </div>

                                @if ($msg->replies->count() > 0)
                                    <div class="mt-1" style="font-size:0.71rem;color:var(--adm-muted);">
                                        <i class="fa fa-comments" style="margin-right:3px;"></i>{{ $msg->replies->count() }}
                                        repl{{ $msg->replies->count() > 1 ? 'ies' : 'y' }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div id="msgNoResults" class="text-center py-4 text-muted" style="display:none;font-size:0.85rem;">
                        <i class="fa fa-search d-block mb-2 opacity-50"></i>No messages match your search.
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat panel --}}
        <div class="col-lg-8">
            <div class="chat-panel">
                <div class="chat-header" id="chatHeader">
                    <div class="chat-placeholder" style="height:auto;padding:0;">
                        <span style="color:var(--adm-muted);font-size:0.88rem;">
                            <i class="fa fa-arrow-left me-2"></i>Select a message to start chatting
                        </span>
                    </div>
                </div>

                <div class="chat-body" id="chatBody">
                    <div class="chat-placeholder">
                        <i class="fa fa-envelope-open-text fa-2x opacity-25"></i>
                        <span>Select a message from the left to view the conversation</span>
                    </div>
                </div>

                <div class="chat-footer" id="chatFooter" style="display:none;">
                    <div id="guestEmailNotice" class="guest-email-notice">
                        <i class="fa fa-envelope" style="flex-shrink:0;"></i>
                        <span>This is a <strong>guest</strong> message — your reply will be sent to <span
                                id="guestEmailAddress" style="font-weight:600;"></span> via email.</span>
                    </div>

                    <div id="resolvedNotice"
                        style="display:none;padding:10px 14px;background:rgba(34,197,94,0.07);border-radius:8px;color:#86efac;font-size:0.82rem;font-weight:600;text-align:center;border:1px solid rgba(34,197,94,0.22);margin-bottom:8px;">
                        <i class="fa fa-circle-check"></i> This conversation has been resolved. No new messages can be sent.
                    </div>

                    <div id="userTypingIndicator"
                        style="display:none;font-size:0.78rem;color:var(--adm-muted);padding:0 2px 8px;align-items:center;gap:6px;">
                        <span>User is typing</span>
                        <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
                    </div>

                    <div style="display:flex;gap:8px;">
                        <textarea id="replyInput" placeholder="Type your reply…" rows="1"></textarea>
                        <button class="btn btn-danger px-3" id="sendBtn" title="Send reply">
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
        window.route = {
            cm: "{{ url('/admin/contact-messages') }}",
        };
    </script>
    <script src="{{ asset('assets/admin/js/contact_messages.js') }}"></script>
@endpush