<?php

namespace App\Http\Controllers\Driver;

use App\Events\Emergency as RideChatEvent;
use App\Http\Controllers\Controller;
use App\Models\Chat\RideChatMessage;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RideChatController extends Controller
{
    private function driver()
    {
        return Auth::guard('driver')->user();
    }

    public function index()
    {
        $driverId = $this->driver()->id;

        $requests = EmergencyRequest::where('driver_id', $driverId)
            ->whereHas('rideChatMessages')
            ->with([
                'user:id,username',
                'user.details:user_id,first_name,last_name',
                'rideChatMessages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->withCount([
                'rideChatMessages as unread_count' => fn ($q) => $q->where('is_read_driver', false),
            ])
            ->get()
            ->sortByDesc(fn ($r) => optional($r->rideChatMessages->first())->created_at)
            ->values();

        return view('driver.pages.ride_chat', compact('requests'));
    }

    public function loadThread($id): JsonResponse
    {
        $driverId = $this->driver()->id;

        $req = EmergencyRequest::where('driver_id', $driverId)
            ->with([
                'user:id,username',
                'user.details:user_id,first_name,last_name',
            ])
            ->findOrFail($id);

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

        // Mark all as read by driver
        RideChatMessage::where('emergency_request_id', $id)
            ->where('is_read_driver', false)
            ->update(['is_read_driver' => true]);

        return response()->json([
            'request' => [
                'id'             => $req->id,
                'rreb_id'        => $req->rreb_id,
                'status'         => $req->status,
                'type'           => $req->type,
                'user_name'      => $req->user?->name ?? 'Unknown',
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

        $driver = $this->driver();

        $req = EmergencyRequest::where('driver_id', $driver->id)->findOrFail($id);

        // Server-side guard: no messages allowed on completed or cancelled rides
        if (in_array((string) $req->status, ['6', '7'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This conversation is closed and no further messages can be sent.',
            ], 403);
        }

        $msg = RideChatMessage::create([
            'emergency_request_id' => $req->id,
            'sender_type'          => 'driver',
            'sender_id'            => $driver->id,
            'sender_name'          => $driver->name,
            'message'              => trim($request->message),
            'is_read_driver'       => true,
            'is_read_admin'        => false,
            'is_read_user'         => false,
        ]);

        // Broadcast to admin in real time
        $req->load(['user:id,username', 'user.details:user_id,first_name,last_name']);
        $isFirst        = $req->rideChatMessages()->count() === 1;
        $requestUnread  = $req->rideChatMessages()->where('is_read_admin', false)->count();
        $totalUnread    = RideChatMessage::where('is_read_admin', false)->distinct('emergency_request_id')->count('emergency_request_id');
        $userName = trim( ($req->user?->details?->first_name ?? '') . ' ' . ($req->user?->details?->last_name ?? ''));

        RideChatEvent::dispatch('admin-dashboard', 'ride-chat-message', [
            'emergency_request_id' => $req->id,
            'rreb_id'              => $req->rreb_id,
            'status'               => (string) $req->status,
            'user_name'            => $userName,
            'driver_name'          => $driver->name,
            'pickup_address'       => $req->pickup_address,
            'is_first_message'     => $isFirst,
            'message'              => [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_name' => $msg->sender_name,
                'message'     => $msg->message,
                'time'        => $msg->created_at->format('d M Y, h:i A'),
            ],
            'request_unread_count' => $requestUnread,
            'total_unread_count'   => $totalUnread,
        ]);

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
                'time'        => $msg->created_at->format('d M Y, h:i A'),
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
        $driver   = $this->driver();
        $req      = EmergencyRequest::where('driver_id', $driver->id)->findOrFail($id);
        $isTyping = (bool) $request->boolean('typing', true);

        $payload = [
            'emergency_request_id' => $req->id,
            'sender_name'          => 'Driver',
            'typing'               => $isTyping,
        ];

        // Broadcast to admin
        RideChatEvent::dispatch('admin-dashboard', 'ride-chat-typing', $payload);

        // Broadcast to the ride owner (user)
        RideChatEvent::dispatch('user.' . $req->user_id, 'ride-chat-typing', $payload);

        return response()->json(['success' => true]);
    }
}
