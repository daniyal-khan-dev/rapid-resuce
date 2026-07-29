<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Log;
use App\Models\Admin\Ambulance;
use App\Models\User\VisitorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    private function noCache($response)
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache')->header('Expires', '0');
    }

    public function index()
    {
        $admin = Auth::guard('admin')->user();

        $stats = [
            'ambulances'     => Ambulance::count(),
            'available_ambs' => Ambulance::where('status', 1)->count(),
            'on_call_ambs'   => Ambulance::where('status', 2)->count(),
            'maintenance'    => Ambulance::where('status', 3)->count(),
        ];

        $recentLogs        = Log::latest()->take(10)->get();
        $recentVisitorLogs = VisitorLog::latest()->take(10)->get();

        // Visitor chart: daily counts for last 90 days
        $visitorRaw = VisitorLog::select( DB::raw("date(created_at) as day"), DB::raw("count(*) as total") )->where('created_at', '>=', now()->subDays(89)->startOfDay())->groupBy('day')->orderBy('day')->pluck('total', 'day');

        // Build a full 90-day array (fill missing days with 0)
        $visitorChart = [];
        for ($i = 89; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $visitorChart[$date] = $visitorRaw[$date] ?? 0;
        }

        return $this->noCache(response()->view('admin.pages.dashboard', compact('admin', 'stats', 'recentLogs', 'recentVisitorLogs', 'visitorChart')));
    }


    public function logs()
    {
        $logs = Log::orderBy('created_at', 'desc')->get();
        $grouped = $logs->groupBy(fn($l) => $l->created_at->format('Y'))->map(fn($yearLogs) =>$yearLogs->groupBy(fn($l) => $l->created_at->format('n')))->sortKeysDesc();
        $years       = $grouped->keys()->toArray();
        $currentYear = now()->year;

        if (!in_array($currentYear, $years) && count($years)) {
            $currentYear = $years[0];
        }

        return view('admin.pages.logs', compact('grouped', 'years', 'currentYear'));
    }
}
