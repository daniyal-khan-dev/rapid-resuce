<?php

namespace App\Http\Controllers\User;

use App\Events\ContactRealtime;
use App\Http\Controllers\Controller;
use App\Mail\GuestContactConfirmationMail;
use App\Models\User\ContactReply;
use App\Models\User\ContactMessage;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    public function submit(Request $request): JsonResponse
    {
        $token = $request->input('g-recaptcha-response', '');
        if (! app(RecaptchaService::class)->verify($token)) {
            return response()->json([
                'errors' => ['recaptcha' => ['Please complete the human verification (reCAPTCHA).']],
            ], 422);
        }

        $request->validate([
            'contact_name'    => 'required|string|max:30',
            'contact_email'   => 'required|email|max:30',
            'contact_phone'   => 'required|string|max:11',
            'contact_subject' => 'required|string|max:50',
            'contact_message' => 'required|string',
        ], [
            'contact_name.required'    => 'Please enter your name.',
            'contact_email.required'   => 'Please enter your email.',
            'contact_email.email'      => 'Please enter a valid Email Address.',
            'contact_phone.required'   => 'Please enter your phone no.',
            'contact_subject.required' => 'Please enter subject.',
            'contact_message.required' => 'Please enter message.',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::guard('users')->id();
            $msg    = ContactMessage::create([
                'user_id' => $userId,
                'name'    => $request->contact_name,
                'email'   => $request->contact_email,
                'phone'   => $request->contact_phone,
                'subject' => $request->contact_subject,
                'message' => $request->contact_message,
            ]);

            $welcome = ContactReply::create([
                'contact_message_id' => $msg->id,
                'sender_type'        => 'admin',
                'message'            => 'Thank you for contacting us. An operator will connect with you shortly.',
                'is_read'            => false,
            ]);

            DB::commit();

            $channels = ['private:contact.admin'];
            if ($msg->user_id) {
                $channels[] = 'private:contact.user.' . $msg->user_id;
            }
            event(new ContactRealtime(
                event: 'contact.submitted',
                channels: $channels,
                data: [
                    'message_id' => $msg->id,
                    'user_id'    => $msg->user_id,
                    'name'       => $msg->name,
                    'email'      => $msg->email,
                    'subject'    => $msg->subject,
                    'message'    => $msg->message,
                    'time'       => $msg->created_at->format('d M Y, h:i A'),
                    'date_short' => $msg->created_at->format('d M'),
                ]
            ));

            if (!$userId) {
                try {
                    Mail::to($msg->email)->send(new GuestContactConfirmationMail($msg));
                } catch (\Throwable $ignored) {}
            }

            return response()->json([
                'success'        => true,
                'message'        => 'Your message has been sent. We will get back to you shortly!',
                'msg_id'         => $msg->id,
                'msg_subject'    => $msg->subject,
                'msg_text'       => $msg->message,
                'msg_time'       => $msg->created_at->format('d M Y, h:i A'),
                'msg_date_short' => $msg->created_at->format('d M'),
                'welcome_reply'  => [
                    'message' => $welcome->message,
                    'time'    => $welcome->created_at->format('d M Y, h:i A'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function sendUserReply(Request $request, $id): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:5000']);
        $userId = Auth::guard('users')->id();
        $msg    = ContactMessage::where('id', $id)->where('user_id', $userId)->firstOrFail();

        if ($msg->is_resolved) {
            return response()->json(['success' => false, 'message' => 'This conversation is resolved.'], 403);
        }

        $reply = ContactReply::create([
            'contact_message_id' => $msg->id,
            'sender_type'        => 'user',
            'message'            => $request->message,
        ]);

        event(new ContactRealtime(
            event: 'user.reply',
            channels: ['private:contact.admin', 'private:contact.user.' . $userId],
            data: [
                'reply_id'           => $reply->id,
                'contact_message_id' => $reply->contact_message_id,
                'user_name'          => $msg?->name ?? 'User',
                'subject'            => $msg?->subject ?? 'Message',
                'message'            => $reply->message,
                'preview'            => Str::limit($reply->message, 80),
                'time'               => $reply->created_at->format('d M Y, h:i A'),
                'date_short'         => $reply->created_at->format('d M'),
            ]
        ));
            
        return response()->json([
            'success' => true,
            'reply'   => [
                'id'      => $reply->id,
                'message' => $reply->message,
                'time'    => $reply->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    public function userTyping(Request $request, $id): JsonResponse
    {
        $userId = Auth::guard('users')->id();
        $msg    = ContactMessage::where('id', $id)->where('user_id', $userId)->firstOrFail();
        event(new ContactRealtime(
            event: 'user.typing',
            channels: ['private:contact.admin'],
            data: [
                'contact_message_id' => $msg->id,
            ]
        )); 
        return response()->json(['ok' => true]);
    }

    public function loadUserThread(Request $request, $id): JsonResponse
    {
        $userId = Auth::guard('users')->id();
        $msg    = ContactMessage::with('replies')->where('id', $id)->where('user_id', $userId)->firstOrFail();

        $newlyRead = $msg->replies()->where('sender_type', 'admin')->where('is_read', false)->count();
        $msg->replies()->where('sender_type', 'admin')->where('is_read', false)->update(['is_read' => true]);

        return response()->json([
            'id'          => $msg->id,
            'subject'     => $msg->subject,
            'message'     => $msg->message,
            'is_resolved' => (bool) $msg->is_resolved,
            'time'        => $msg->created_at->format('d M Y, h:i A'),
            'newly_read'  => $newlyRead,
            'replies'     => $msg->replies->map(fn($r) => [
                'id'          => $r->id,
                'sender_type' => $r->sender_type,
                'message'     => $r->message,
                'time'        => $r->created_at->format('d M Y, h:i A'),
            ]),
        ]);
    }
}
