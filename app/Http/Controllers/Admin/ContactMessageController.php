<?php

namespace App\Http\Controllers\Admin;

use App\Events\ContactRealtime;
use App\Http\Controllers\Controller;
use App\Models\Admin\ContactReply;
use App\Models\User\ContactMessage;
use App\Mail\AdminReplyMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::with(['user', 'replies'])->orderBy('admin_read', 'asc')->orderByDesc('created_at')->get();
        $unread   = $messages->where('admin_read', false)->count();
        return view('admin.pages.contact_messages', compact('messages', 'unread'));
    }

    public function loadThread(Request $request, $id): JsonResponse
    {
        $msg = ContactMessage::with(['user.details', 'replies'])->findOrFail($id);
        $wasUnread          = !$msg->admin_read;
        $unreadRepliesCount = $msg->replies()->where('sender_type', 'user')->where('is_read', false)->count();
        $msg->update(['admin_read' => true]);
        $msg->replies()->where('sender_type', 'user')->where('is_read', false)->update(['is_read' => true]);
        $markedCount = ($wasUnread ? 1 : 0) + $unreadRepliesCount;
        
        if ($markedCount > 0) {
            try {
                event(new ContactRealtime(
                    event: 'message.read',
                    channels: ['private:contact.admin'],
                    data: [
                        'message_id' => $id,
                        'count'      =>  $markedCount,
                    ]
                )); 
            } catch (\Throwable $ignored) {}
        }

        $profilePictureUrl = null;
        if ($msg->user && $msg->user->details) {
            $pic = $msg->user->details->profile_picture;
            if ($pic && $pic !== 'default.jpg') {
                $profilePictureUrl = asset('assets/user/img/users/' . $pic);
            }
        }

        return response()->json([
            'id'                  => $msg->id,
            'name'                => $msg->name,
            'email'               => $msg->email,
            'subject'             => $msg->subject,
            'message'             => $msg->message,
            'is_user'             => (bool) $msg->user_id,
            'user_id'             => $msg->user_id,
            'is_resolved'         => (bool) $msg->is_resolved,
            'time'                => $msg->created_at->format('d M Y, h:i A'),
            'marked_count'        => $markedCount,
            'profile_picture_url' => $profilePictureUrl,
            'replies'             => $msg->replies->map(fn($r) => [
                'id'          => $r->id,
                'sender_type' => $r->sender_type,
                'message'     => $r->message,
                'time'        => $r->created_at->format('d M Y, h:i A'),
            ]),
        ]);
    }

    public function sendReply(Request $request, $id): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:5000']);
        $msg = ContactMessage::findOrFail($id);

        if ($msg->is_resolved) {
            return response()->json(['success' => false, 'message' => 'This conversation is resolved.'], 403);
        }

        $reply = ContactReply::create([
            'contact_message_id' => $msg->id,
            'sender_type'        => 'admin',
            'message'            => $request->message,
        ]);

        if (!$msg->user_id) {
            try {
                Mail::to($msg->email)->send(new AdminReplyMail($msg, $reply));
            } catch (\Throwable $ignored) {}
        }

        $channels = ['private:contact.admin'];
        if ($msg->user_id) {
            $channels[] = 'private:contact.user.' . $msg->user_id;
        }
        try {
            event(new ContactRealtime(
                event: 'admin.reply',
                channels: $channels,
                data: [
                    'contact_message_id' => $msg->id,
                    'reply_id'           => $reply->id,
                    'message'            => $reply->message,
                    'time'               => $reply->created_at->format('d M Y, h:i A'),
                ]
            ));
        } catch (\Throwable $ignored) {}

        return response()->json([
            'success' => true,
            'reply'   => [
                'id'      => $reply->id,
                'message' => $reply->message,
                'time'    => $reply->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    public function adminTyping(Request $request, $id): JsonResponse
    {
        $msg = ContactMessage::findOrFail($id);
        if ($msg->user_id) {
            event(new ContactRealtime(
                event: 'admin.typing',
                channels: ['private:contact.user.' . $msg->user_id],
                data: [
                    'contact_message_id' => $msg->id,
                ]
            ));
        }
        return response()->json(['ok' => true]);
    }

    public function resolve(Request $request, $id): JsonResponse
    {
        $msg = ContactMessage::findOrFail($id);
        if ($msg->is_resolved != 1) {
            $msg->update(['is_resolved' => true]);

            $channels = ['private:contact.admin'];
            if ($msg->user_id) {
                $channels[] = 'private:contact.user.' . $msg->user_id;
            }
            try {
                event(new ContactRealtime(
                    event: 'resolved',
                    channels: $channels,
                    data: [
                        'message_id' => $msg->id,
                    ]
                ));
            } catch (\Throwable $ignored) {}
        }
        return response()->json(['success' => true]);
    }
}
