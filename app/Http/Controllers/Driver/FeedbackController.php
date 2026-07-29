<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Events\Emergency;
use App\Models\User\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        $query = Feedback::with([
            'user.details',
            'request.driver',
            'request.ambulance',
        ])->whereHas('request', function ($requestQuery) use ($driver) {
            $requestQuery->where('driver_id', $driver->id);
        });

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($feedbackQuery) use ($search) {
                $feedbackQuery
                    ->where('feedback.message', 'like', "%{$search}%")
                    ->orWhere('feedback.name', 'like', "%{$search}%")
                    ->orWhere('feedback.email', 'like', "%{$search}%")
                    ->orWhereHas('request', function ($requestQuery) use ($search) {
                        $requestQuery
                            ->where('rreb_id', 'like', "%{$search}%")
                            ->orWhere('hospital_name', 'like', "%{$search}%")
                            ->orWhere('pickup_address', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user.details', function ($detailsQuery) use ($search) {
                        $detailsQuery
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating_high' => $query->orderByDesc('rating')->orderByDesc('created_at'),
            'rating_low' => $query->orderBy('rating')->orderByDesc('created_at'),
            default => $query->latest(),
        };

        $feedback = $query->paginate(20)->withQueryString();

        return view('driver.pages.feedback', compact('driver', 'feedback'));
    }

    public function markViewed(int $id): JsonResponse
    {
        $driver = Auth::guard('driver')->user();

        $feedback = Feedback::whereKey($id)
            ->whereNull('driver_viewed_at')
            ->whereHas('request', function ($requestQuery) use ($driver) {
                $requestQuery->where('driver_id', $driver->id);
            })
            ->first();

        $updated = 0;
        if ($feedback) {
            $updated = $feedback->update(['driver_viewed_at' => now()]);
        }

        $unviewedCount = Feedback::whereNull('driver_viewed_at')
            ->whereHas('request', function ($requestQuery) use ($driver) {
                $requestQuery->where('driver_id', $driver->id);
            })
            ->count();

        if ($updated > 0) {
            event(new Emergency('driver.' . $driver->id, 'feedback-viewed', [
                'feedback_id' => $id,
                'driver_id' => $driver->id,
                'driver_unviewed_count' => $unviewedCount,
            ]));
        }

        return response()->json([
            'success' => true,
            'feedback_id' => $id,
            'driver_unviewed_count' => $unviewedCount,
        ]);
    }
}