<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\VisitorLog;

class VisitorLogController extends Controller
{
    public function index()
    {
        $logs = VisitorLog::orderByDesc('created_at')->get();

        $grouped = $logs->groupBy(fn($l) => $l->created_at->format('Y'))->map(fn($yearLogs) =>$yearLogs->groupBy(fn($l) => $l->created_at->format('n')))->sortKeysDesc();

        $years       = $grouped->keys()->toArray();
        $currentYear = now()->year;
        if (!in_array($currentYear, $years) && count($years)) {
            $currentYear = $years[0];
        }

        $total  = $logs->count();
        $mobile = $logs->where('is_mobile', true)->count();
        $today  = $logs->filter(fn($l) => $l->created_at->isToday())->count();

        return view('admin.pages.visitor_logs', compact('grouped', 'years', 'currentYear', 'total', 'mobile', 'today'));
    }
}
