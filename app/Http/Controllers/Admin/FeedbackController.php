<?php

namespace App\Http\Controllers\Admin;

use App\Events\Emergency;
use App\Http\Controllers\Controller;
use App\Models\User\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = Feedback::with([
            'user.details',
            'request.driver',
            'request.ambulance',
        ]);

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
                    })
                    ->orWhereHas('request.driver', function ($driverQuery) use ($search) {
                        $driverQuery
                            ->where('name', 'like', "%{$search}%")
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

        $totalFeedback = Feedback::count();
        $averageRating = round((float) (Feedback::avg('rating') ?? 0), 1);

        return view('admin.pages.feedback', compact(
            'feedback',
            'totalFeedback',
            'averageRating',
        ));
    }

    public function markViewed(int $id): JsonResponse
    {
        $updated = Feedback::whereKey($id)
            ->whereNull('viewed_at')
            ->update(['viewed_at' => now()]);

        $unviewedCount = Feedback::whereNull('viewed_at')->count();

        if ($updated > 0) {
            event(new Emergency('admin-dashboard', 'feedback-viewed', [
                'feedback_id'    => $id,
                'unviewed_count' => $unviewedCount,
            ]));
        }

        return response()->json([
            'success'        => true,
            'feedback_id'    => $id,
            'unviewed_count' => $unviewedCount,
        ]);
    }
}