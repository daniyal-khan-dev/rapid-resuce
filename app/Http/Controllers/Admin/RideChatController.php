<?php

namespace App\Http\Controllers\Admin;
use App\Events\Emergency as RideChatEvent;
use App\Http\Controllers\Controller;
use App\Models\Chat\RideChatMessage;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RideChatController extends Controller
{
    public function index()
    {
        $requests = EmergencyRequest::whereHas('rideChatMessages')
            ->with([
                'user:id,username',
                'user.details:user_id,first_name,last_name',
                'driver:id,name',
                'rideChatMessages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->withCount([
                'rideChatMessages as unread_count' => fn ($q) => $q->where('is_read_admin', false),
            ])
            ->get()
            ->sortByDesc(fn ($r) => optional($r->rideChatMessages->first())->created_at)
            ->values();

        return view('admin.pages.ride_chat', compact('requests'));
    }

    public function loadThread($id): JsonResponse
    {
        $req = EmergencyRequest::with([
            'user:id,username',
            'user.details:user_id,first_name,last_name',
            'driver:id,name',
        ])->findOrFail($id);

        $messages = RideChatMessage::where('emergency_request_id', $id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'message'     => $m->message,
                'time'        => $m->created_at->format('d M Y, h:i A'),
            ]);

        // Mark all unread as read by admin
        RideChatMessage::where('emergency_request_id', $id)->where('is_read_admin', false)->update(['is_read_admin' => true]);
        $userName = trim( ($req->user?->details?->first_name ?? '') . ' ' . ($req->user?->details?->last_name ?? ''));

        return response()->json([
            'request' => [
                'id'             => $req->id,
                'rreb_id'        => $req->rreb_id,
                'status'         => $req->status,
                'type'           => $req->type,
                'user_name'      => $userName,
                'driver_name'    => $req->driver?->name ?? null,
                'pickup_address' => $req->pickup_address,
            ],
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $req   = EmergencyRequest::findOrFail($id);
        $admin = Auth::guard('admin')->user();

        // Server-side guard: no messages allowed on completed or cancelled rides
        if (in_array((string) $req->status, ['6', '7'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This conversation is closed and no further messages can be sent.',
            ], 403);
        }

        $msg = RideChatMessage::create([
            'emergency_request_id' => $req->id,
            'sender_type'          => 'admin',
            'sender_id'            => $admin->id,
            'sender_name'          => $admin->name,
            'message'              => trim($request->message),
            'is_read_driver'       => false,
            'is_read_admin'        => true,
            'is_read_user'         => false,
        ]);

        // Broadcast to the assigned driver in real time
        if ($req->driver_id) {
            $req->load(['user:id,username', 'user.details:user_id,first_name,last_name']);
            $userName            = trim(($req->user?->details?->first_name ?? '') . ' ' . ($req->user?->details?->last_name ?? ''));
            $isFirst             = RideChatMessage::where('emergency_request_id', $req->id)->count() === 1;
            $driverRequestUnread = RideChatMessage::where('emergency_request_id', $req->id)
                                    ->where('is_read_driver', false)->count();
            $driverTotalUnread   = RideChatMessage::where('is_read_driver', false)
                                    ->whereHas('emergencyRequest', fn ($q) => $q->where('driver_id', $req->driver_id))
                                    ->distinct('emergency_request_id')->count('emergency_request_id');

            RideChatEvent::dispatch('driver.' . $req->driver_id, 'ride-chat-message', [
                'emergency_request_id' => $req->id,
                'rreb_id'              => $req->rreb_id,
                'status'               => (string) $req->status,
                'user_name'            => $userName ?: ($req->user?->username ?? 'Unknown'),
                'is_first_message'     => $isFirst,
                'message'              => [
                    'id'          => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender_name,
                    'message'     => $msg->message,
                    'time'        => $msg->created_at->format('d M Y, h:i A'),
                ],
                'request_unread_count' => $driverRequestUnread,
                'total_unread_count'   => $driverTotalUnread,
            ]);
        }
        
        // Broadcast to the user (ride owner) in real time
        $userRequestUnread = RideChatMessage::where('emergency_request_id', $req->id)
                              ->where('is_read_user', false)->count();
        RideChatEvent::dispatch('user.' . $req->user_id, 'ride-chat-message', [
            'emergency_request_id' => $req->id,
            'message'              => [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_name' => $msg->sender_name,
                'message'     => $msg->message,
                'time'        => $msg->created_at->format('h:i A'),
            ],
            'request_unread_count' => $userRequestUnread,
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_name' => $msg->sender_name,
                'message'     => $msg->message,
                'time'        => $msg->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    public function typing(Request $request, $id): JsonResponse
    {
        $req      = EmergencyRequest::findOrFail($id);
        $isTyping = (bool) $request->boolean('typing', true);

        $payload = [
            'emergency_request_id' => $req->id,
            'sender_name'          => 'Admin',
            'typing'               => $isTyping,
        ];

        // Broadcast to the ride owner (user)
        RideChatEvent::dispatch('user.' . $req->user_id, 'ride-chat-typing', $payload);

        // Broadcast to the assigned driver
        if ($req->driver_id) {
            RideChatEvent::dispatch('driver.' . $req->driver_id, 'ride-chat-typing', $payload);
        }

        return response()->json(['success' => true]);
    }
}
