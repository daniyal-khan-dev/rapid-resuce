<?php

namespace App\Http\Controllers\User;

use App\Events\Emergency as RideChatEvent;
use App\Http\Controllers\Controller;
use App\Models\Chat\RideChatMessage;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RideChatController extends Controller
{
    /**
     * Return all messages for the given emergency request (user must own it).
     * Also marks all messages as read by the user.
     */
    public function messages(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('users')->user();
        $req  = EmergencyRequest::where('user_id', $user->id)->findOrFail($id);

        $messages = RideChatMessage::where('emergency_request_id', $req->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'message'     => $m->message,
                'time'        => $m->created_at->format('h:i A'),
            ]);

        // Mark all messages as read by the user
        RideChatMessage::where('emergency_request_id', $req->id)
            ->where('is_read_user', false)
            ->update(['is_read_user' => true]);

        return response()->json(['success' => true, 'messages' => $messages]);
    }
    
    /**
     * Mark all messages in this thread as read by the user.
     * Called when the user opens the chat box (subsequent opens after initial load).
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('users')->user();
        $req  = EmergencyRequest::where('user_id', $user->id)->findOrFail($id);

        RideChatMessage::where('emergency_request_id', $req->id)
            ->where('is_read_user', false)
            ->update(['is_read_user' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Broadcast a typing indicator to the admin and driver (not persisted).
     */
    public function typing(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('users')->user();
        $req  = EmergencyRequest::where('user_id', $user->id)->findOrFail($id);

        $user->loadMissing('details');
        $senderName = trim(($user->details?->first_name ?? '') . ' ' . ($user->details?->last_name ?? ''));
        if (!$senderName) {
            $senderName = $user->username;
        }

        $isTyping = (bool) $request->boolean('typing', true);

        $payload = [
            'emergency_request_id' => $req->id,
            'sender_name'          => $senderName,
            'typing'               => $isTyping,
        ];

        RideChatEvent::dispatch('admin-dashboard', 'ride-chat-typing', $payload);

        if ($req->driver_id) {
            RideChatEvent::dispatch('driver.' . $req->driver_id, 'ride-chat-typing', $payload);
        }

        return response()->json(['success' => true]);
    }

    
    /**
     * Save a new user message for the given emergency request.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $user = Auth::guard('users')->user();
        $req  = EmergencyRequest::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $msg = RideChatMessage::create([
            'emergency_request_id' => $req->id,
            'sender_type'          => 'user',
            'sender_id'            => $user->id,
            'sender_name'          => $user->username,
            'message'              => trim($validated['message']),
            'is_read_driver'       => false,
            'is_read_admin'        => false,
            'is_read_user'         => true,
        ]);

        // Broadcast to admin in real time
        $req->load(['driver:id,name', 'user:id,username', 'user.details:user_id,first_name,last_name']);
        $isFirst = $req->rideChatMessages()->count() === 1;
        $userName = trim(($req->user?->details?->first_name ?? '') . ' ' . ($req->user?->details?->last_name ?? ''));
        $payload = [
            'emergency_request_id' => $req->id,
            'rreb_id'              => $req->rreb_id,
            'status'               => (string) $req->status,
            'user_name'            => $userName,
            'is_first_message'     => $isFirst,
            'message'              => [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_name' => $msg->sender_name,
                'message'     => $msg->message,
                'time'        => $msg->created_at->format('d M Y, h:i A'),
            ],
        ];
        
        // -------------------- Admin --------------------
        $adminPayload = array_merge($payload, [
            'driver_name'          => $req->driver?->name,
            'pickup_address'       => $req->pickup_address,
            'request_unread_count' => $req->rideChatMessages()->where('is_read_admin', false)->count(),
            'total_unread_count'   => RideChatMessage::where('is_read_admin', false)->distinct('emergency_request_id')->count('emergency_request_id'),
        ]);
        RideChatEvent::dispatch('admin-dashboard', 'ride-chat-message', $adminPayload);        
        
        // -------------------- Driver --------------------
        $driverPayload = array_merge($payload, [
            'request_unread_count' => $req->rideChatMessages()->where('is_read_driver', false)->count(),
            'total_unread_count'   => RideChatMessage::where('is_read_driver', false)->whereHas('emergencyRequest', fn ($q) => $q->where('driver_id', $req->driver_id))->distinct('emergency_request_id')->count('emergency_request_id'),
        ]);        
        RideChatEvent::dispatch('driver.' . $req->driver_id, 'ride-chat-message', $driverPayload);

        return response()->json([
            'success' => true,
            'message' => [
                'id'          => $msg->id,
                'sender_type' => $msg->sender_type,
                'sender_name' => $msg->sender_name,
                'message'     => $msg->message,
                'time'        => $msg->created_at->format('h:i A'),
            ],
        ]);
    }
}
