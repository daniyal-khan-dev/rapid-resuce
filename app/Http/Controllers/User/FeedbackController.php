<?php

namespace App\Http\Controllers\User;

use App\Events\Emergency;
use App\Http\Controllers\Controller;
use App\Models\User\EmergencyRequest;
use App\Models\User\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        $req = EmergencyRequest::with('driver')->findOrFail($id);

        // Only completed rides may be rated
        if ($req->status !== '6') {
            return response()->json([
                'success' => false,
                'message' => 'You can only rate a completed ride.',
            ], 422);
        }

        // One rating per ride (any user / guest)
        if (Feedback::where('request_id', $id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this ride.',
            ], 422);
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'message' => 'nullable|string|max:1000',
        ]);

        $user = Auth::guard('users')->user();

        $feedback = Feedback::create([
            'user_id'    => $user?->id,
            'request_id' => $req->id,
            'rating'     => $validated['rating'],
            'message'    => $validated['message'] ?? null,
            'name'       => $user?->details?->first_name,
            'email'      => $user?->username,
        ]);

        $feedback->load([
            'user.details',
            'request.driver',
            'request.ambulance',
        ]);

        $channels = ['admin-dashboard'];
        if ($feedback->request?->driver_id) {
            $channels[] = 'driver.' . $feedback->request->driver_id;
        }

        event(new Emergency($channels, 'feedback-created', [
            'feedback_id'    => $feedback->id,
            'feedback'       => $this->feedbackPayload($feedback),
            'total_count'    => Feedback::count(),
            'average_rating' => round((float) (Feedback::avg('rating') ?? 0), 1),
            'unviewed_count' => Feedback::whereNull('viewed_at')->count(),
            'driver_unviewed_count' => $feedback->request?->driver_id
                ? Feedback::whereNull('driver_viewed_at')
                    ->whereHas('request', function ($requestQuery) use ($feedback) {
                        $requestQuery->where('driver_id', $feedback->request->driver_id);
                    })
                    ->count()
                : 0,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }

    private function feedbackPayload(Feedback $feedback): array
    {
        $userDetails = $feedback->user?->details;
        $ride = $feedback->request;

        return [
            'id' => $feedback->id,
            'driver_id' => $ride?->driver_id,
            'viewed' => $feedback->viewed_at !== null,
            'driver_viewed' => $feedback->driver_viewed_at !== null,
            'booking_id' => $ride?->rreb_id ?? '—',
            'booking_url' => $ride
                ? route('admin.emergency.past-rides', ['search' => $ride->rreb_id])
                : null,
            'user_name' => trim(($userDetails?->first_name ?? '') . ' ' . ($userDetails?->last_name ?? ''))
                ?: ($feedback->name ?: 'Guest'),
            'user_email' => $feedback->user?->username ?? $feedback->email ?? '—',
            'user_phone' => $userDetails?->phone ?? $ride?->mobile_no ?? '—',
            'driver_name' => $ride?->driver?->name ?? '—',
            'driver_phone' => $ride?->driver?->phone ?? '—',
            'rating' => (int) $feedback->rating,
            'message' => $feedback->message ?: 'No written feedback provided.',
            'submitted_at' => $feedback->created_at?->format('d M Y, h:i A') ?? '—',
            'created_at' => $feedback->created_at?->toIso8601String(),
            'ride_type' => match ((string) ($ride?->type ?? '')) {
                '1' => 'Emergency',
                '2' => 'Non-Emergency',
                default => $ride?->type ?? '—',
            },
            'status' => match ((string) ($ride?->status ?? '')) {
                '6' => 'Completed',
                '7' => 'Cancelled',
                default => $ride?->status ?? '—',
            },
            'hospital_name' => $ride?->hospital_name ?? '—',
            'pickup_address' => $ride?->pickup_address ?? '—',
            'ambulance' => $ride?->ambulance?->vehicle_number ?? '—',
            'requested_at' => $ride?->created_at?->format('d M Y, h:i A') ?? '—',
            'dispatched_at' => $ride?->dispatched_at?->format('d M Y, h:i A') ?? '—',
            'completed_at' => $ride?->completed_at?->format('d M Y, h:i A') ?? '—',
        ];
    }
}
