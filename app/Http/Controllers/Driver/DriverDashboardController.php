<?php

namespace App\Http\Controllers\Driver;

use App\Events\AdminContentUpdate;
use App\Events\DriverUpdated;
use App\Http\Controllers\Controller;
use App\Models\User\EmergencyRequest;
use App\Models\Driver\Driver;
use App\Models\Admin\Ambulance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DriverDashboardController extends Controller
{
    public function index()
    {
        $driver = Auth::guard('driver')->user();

        $total     = EmergencyRequest::where('driver_id', $driver->id)->count();
        $completed = EmergencyRequest::where('driver_id', $driver->id)->where('status', '6')->count();
        $cancelled = EmergencyRequest::where('driver_id', $driver->id)->where('status', '7')->count();
        $active    = EmergencyRequest::where('driver_id', $driver->id)->whereNotIn('status', ['6', '7', '8'])->where('status', '!=', '1')->count();
        $pending   = EmergencyRequest::where('driver_id', $driver->id)->where('status', ['8'])->count();
        $today     = EmergencyRequest::where('driver_id', $driver->id)->whereDate('dispatched_at', today())->count();

        $history = EmergencyRequest::with(['ambulance'])->where('driver_id', $driver->id)->latest()->limit(10)->get();

        return view('driver.pages.dashboard', compact(
            'driver', 'total', 'completed', 'cancelled', 'active', 'pending', 'today', 'history'
        ));
    }

    public function requests(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        $query = EmergencyRequest::with(['ambulance'])
            ->where('driver_id', $driver->id);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->whereNotIn('status', ['6', '7']);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rreb_id', 'like', "%$s%")
                  ->orWhere('hospital_name', 'like', "%$s%")
                  ->orWhere('pickup_address', 'like', "%$s%")
                  ->orWhere('mobile_no', 'like', "%$s%");
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'     => EmergencyRequest::where('driver_id', $driver->id)->whereNotIn('status', ['6', '7'])->count(),
            'active'    => EmergencyRequest::where('driver_id', $driver->id)->whereNotIn('status', ['6', '7', '8'])->count(),
            'pending'    => EmergencyRequest::where('driver_id', $driver->id)->where('status', '8')->count(),
        ];

        return view('driver.pages.requests', compact('driver', 'requests', 'stats'));
    }
    
    public function pastRides(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        $query = EmergencyRequest::with(['ambulance'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['6', '7']);

        if ($request->filled('status') && in_array($request->status, ['6', '7'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rreb_id', 'like', "%$s%")
                  ->orWhere('hospital_name', 'like', "%$s%")
                  ->orWhere('pickup_address', 'like', "%$s%")
                  ->orWhere('mobile_no', 'like', "%$s%");
            });
        }

        $dateFilter = $request->get('date_filter', 'all');
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($dateFilter === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $rides = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'completed' => EmergencyRequest::where('driver_id', $driver->id)->where('status', '6')->count(),
            'cancelled' => EmergencyRequest::where('driver_id', $driver->id)->where('status', '7')->count(),
        ];

        return view('driver.pages.past_rides', compact('driver', 'rides', 'stats'));
    }
    
    public function profile()
    {
        $driver = Auth::guard('driver')->user();
        return view('driver.pages.profile', compact('driver'));
    }

    public function updateProfile(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        $driver->name  = $request->name;
        $driver->phone = $request->phone;
        $driver->save();

        $fresh = Driver::withCount([
            'emergencyRequests as total_jobs',
            'emergencyRequests as completed_jobs' => fn ($q) => $q->where('status', '6'),
        ])->find($driver->id);

        event(new DriverUpdated(
            entity: 'driverAdminUpdated',
            action: 'updated',
            data: [
                'id'             => $fresh->id,
                'name'           => $fresh->name,
                'username'       => $fresh->username,
                'email'          => $fresh->email,
                'phone'          => $fresh->phone,
                'license_no'     => $fresh->license_no,
                'photo'          => $fresh->photo,
                'photo_url'      => ($fresh->photo && $fresh->photo !== 'default.jpg')
                                        ? asset('assets/driver/img/' . $fresh->photo)
                                        : null,
                'status'         => $fresh->status,
                'availability'   => $fresh->availability,
                'total_jobs'     => $fresh->total_jobs,
                'completed_jobs' => $fresh->completed_jobs,
                'added_by'       => $fresh->added_by,
                'created_at'     => $fresh->created_at,
                'updated_by'     => $fresh->updated_by,
                'updated_at'     => $fresh->updated_at,
            ]
        ));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $driver->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        $driver->password = Hash::make($request->password);
        $driver->save();

        return back()->with('success', 'Password changed successfully.');
    }

    /**
     * Receive a live GPS fix from the driver's own browser (via
     * navigator.geolocation.watchPosition) and rebroadcast it over the
     * existing "drivers-update" Reverb channel so the Driver Dashboard's
     * live map can update in real time. Reuses the existing DriverUpdated
     * event — no new broadcast event is introduced.
     */
    public function updateLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lat'      => 'required|numeric|between:-90,90',
            'lng'      => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'heading'  => 'nullable|numeric',
        ]);

        $driver = Auth::guard('driver')->user();

        $prevLat   = $driver->lat;
        $prevLng   = $driver->lng;
        $prevSeenAt = $driver->last_seen_at;

        $driver->lat          = $request->lat;
        $driver->lng          = $request->lng;
        $driver->last_seen_at = now();
        $driver->save();

        // Compute speed (km/h) from the previous fix, when available.
        $speedKmh = null;
        if ($prevLat !== null && $prevLng !== null && $prevSeenAt) {
            $seconds = $driver->last_seen_at->diffInSeconds($prevSeenAt);
            if ($seconds > 0) {
                $distanceKm = $this->haversineKm((float) $prevLat, (float) $prevLng, (float) $driver->lat, (float) $driver->lng);
                $speedKmh   = round($distanceKm / ($seconds / 3600), 1);
                // Discard implausible spikes (GPS jitter while stationary, etc.)
                if ($speedKmh > 220) {
                    $speedKmh = null;
                }
            }
        }

        event(new DriverUpdated(
            entity: 'driverLocationUpdated',
            action: 'updated',
            data: [
                'id'           => $driver->id,
                'name'         => $driver->name,
                'status'       => $driver->status,
                'availability' => $driver->availability,
                'lat'          => (float) $driver->lat,
                'lng'          => (float) $driver->lng,
                'accuracy'     => $request->filled('accuracy') ? (float) $request->accuracy : null,
                'heading'      => $request->filled('heading') ? (float) $request->heading : null,
                'speed'        => $speedKmh,
                'updated_at'   => $driver->last_seen_at->toIso8601String(),
            ]
        ));

        return response()->json(['success' => true]);
    }

    public function showRequest($id): \Illuminate\Http\JsonResponse
    {
        $driver = Auth::guard('driver')->user();
        $req    = EmergencyRequest::with(['ambulance'])
            ->where('driver_id', $driver->id)
            ->findOrFail($id);

        $ambulanceTypeMap = [
            '1' => 'BLS — Basic Life Support',
            '2' => 'ALS — Advanced Life Support',
            '3' => 'CCT — Critical Care Transport',
            '4' => 'Neonatal',
            '5' => 'Air Ambulance',
        ];

        return response()->json([
            'success' => true,
            'request' => [
                'id'             => $req->id,
                'rreb_id'        => $req->rreb_id,
                'mobile_no'      => $req->mobile_no,
                'email'          => $req->email,
                'type'           => $req->type,
                'status'         => $req->status,
                'pickup_address' => $req->pickup_address,
                'pickup_lat'     => $req->pickup_lat     ? (float) $req->pickup_lat     : null,
                'pickup_lng'     => $req->pickup_lng     ? (float) $req->pickup_lng     : null,
                'hospital_name'  => $req->hospital_name,
                'hospital_lat'   => $req->hospital_lat  ? (float) $req->hospital_lat   : null,
                'hospital_lng'   => $req->hospital_lng  ? (float) $req->hospital_lng   : null,
                'ambulance_no'   => $req->ambulance?->vehicle_number,
                'ambulance_type' => $ambulanceTypeMap[$req->ambulance?->type ?? ''] ?? ($req->ambulance?->type),
                'notes'          => $req->notes,
                'dispatched_at'  => $req->dispatched_at?->format('d M Y, H:i'),
                'completed_at'   => $req->completed_at?->format('d M Y, H:i'),
                'created_at'     => $req->created_at->format('d M Y, H:i'),
                'accepted_lat'   => $req->accepted_lat  ? (float) $req->accepted_lat   : null,
                'accepted_lng'   => $req->accepted_lng  ? (float) $req->accepted_lng   : null,
                // Driver's own current position for the live map
                'driver_lat'     => $driver->lat         ? (float) $driver->lat         : null,
                'driver_lng'     => $driver->lng         ? (float) $driver->lng         : null,
            ],
        ]);
    }

    public function updateRequestStatus(\Illuminate\Http\Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $driver = Auth::guard('driver')->user();
        $req    = EmergencyRequest::where('driver_id', $driver->id)->findOrFail($id);

        $action = $request->input('action');

        // Allowed transitions: action => [from statuses] => to status
        $transitions = [
            'accept'      => ['from' => ['8'], 'to' => '2'],
            'reject'      => ['from' => ['8'], 'to' => '1', 'clear_driver' => true],
            'en_route'    => ['from' => ['2'], 'to' => '3'],
            'arrived'     => ['from' => ['3'], 'to' => '4'],
            'transporting'=> ['from' => ['4'], 'to' => '5'],
            'complete'    => ['from' => ['5'], 'to' => '6'],
            'cancel'      => ['from' => ['2', '3', '4', '5', '8'], 'to' => '7'],
        ];

        if (!isset($transitions[$action])) {
            return response()->json(['success' => false, 'message' => 'Invalid action.'], 422);
        }

        $t = $transitions[$action];
        if (!in_array($req->status, $t['from'])) {
            return response()->json(['success' => false, 'message' => 'Cannot perform this action from the current status.'], 422);
        }

        $req->status = $t['to'];
        $userPayload = [
            'id'     => $req->id,
            'status' => $req->status,
            'action' => $action,
        ];

        if ($action === 'accept') {
            $req->accepted_lat = $driver->lat;
            $req->accepted_lng = $driver->lng;

            $ambulance = Ambulance::findOrFail($req->ambulance_id);

            $driver->availability = '2';
            $driver->save();
            event(new DriverUpdated(
                entity: 'driverAdminUpdated',
                action: 'updated',
                data: [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'username' => $driver->username,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'license_no' => $driver->license_no,
                    'status' => $driver->status,
                    'availability' => $driver->availability,
                    'lat' => $driver->lat,
                    'lng' => $driver->lng,
                    'updated_at' => now()->toIso8601String(),
                ]
            ));

            $ambulance->status = '2';
            $ambulance->save();
            event(new AdminContentUpdate(
                entity: 'ambulance',
                action: 'updated',
                data: [
                    'id'               => $ambulance->id,
                    'vehicle_number'   => $ambulance->vehicle_number,
                    'type'             => $ambulance->type,
                    'equipment_level'  => $ambulance->equipment_level,
                    'status'           => $ambulance->status,
                    'driver_id'        => $ambulance->driver_id,
                    'driver_name'      => optional($ambulance->driver)->name,
                    'notes'            => $ambulance->notes,
                    'card_title'       => $ambulance->card_title,
                    'card_description' => $ambulance->card_description,
                    'card_image'       => $ambulance->card_image,
                    'card_features'    => $ambulance->card_features,
                    'card_rating'      => $ambulance->card_rating,
                    'card_trips'       => $ambulance->card_trips,
                    'added_by'         => $ambulance->added_by,
                    'created_at'       => $ambulance->created_at,
                    'updated_by'       => $ambulance->updated_by,
                    'updated_at'       => $ambulance->updated_at,
                ]
            ));

            $userPayload['driver_id']  = $driver->id;
            $userPayload['driver_name'] = $driver->name;
            $userPayload['driver_phone'] = $driver->phone;
            $userPayload['ambulance_vehicle_number'] = $ambulance->vehicle_number;
            $userPayload['ambulance_type'] = $ambulance->type;
            $userPayload['dispatched_at'] = $req->dispatched_at;
            $userPayload['driver_lat'] = $req->accepted_lat ? (float) $req->accepted_lat : null;
            $userPayload['driver_lng'] = $req->accepted_lng ? (float) $req->accepted_lng : null;
        }

        $req->save();

        if (in_array($action, ['complete', 'cancel'])) {
            $req->completed_at = now();
        }

        if (in_array($action, ['complete', 'cancel', 'reject'])) {
            $drivers = Driver::withCount([
                'emergencyRequests as total_jobs',
                'emergencyRequests as completed_jobs' => fn ($q) => $q->where('status', 6),
            ])->find($driver->id);
            
            $driver->availability = '1';
            $driver->save();
            event(new DriverUpdated(
                entity: 'driverAdminUpdated',
                action: 'updated',
                data: [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'username' => $driver->username,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'license_no' => $driver->license_no,
                    'total_jobs' => $drivers->total_jobs ?? 0,
                    'completed_jobs' => $drivers->completed_jobs ?? 0,
                    'status' => $driver->status,
                    'availability' => $driver->availability,
                    'lat' => $driver->lat,
                    'lng' => $driver->lng,
                    'updated_at' => now()->toIso8601String(),
                ]
            ));

            $ambulance = Ambulance::findOrFail($req->ambulance_id);

            $ambulance->status = '1';
            $ambulance->save();
            event(new AdminContentUpdate(
                entity: 'ambulance',
                action: 'updated',
                data: [
                    'id'               => $ambulance->id,
                    'vehicle_number'   => $ambulance->vehicle_number,
                    'type'             => $ambulance->type,
                    'equipment_level'  => $ambulance->equipment_level,
                    'status'           => $ambulance->status,
                    'driver_id'        => $ambulance->driver_id,
                    'driver_name'      => optional($ambulance->driver)->name,
                    'notes'            => $ambulance->notes,
                    'card_title'       => $ambulance->card_title,
                    'card_description' => $ambulance->card_description,
                    'card_image'       => $ambulance->card_image,
                    'card_features'    => $ambulance->card_features,
                    'card_rating'      => $ambulance->card_rating,
                    'card_trips'       => $ambulance->card_trips,
                    'added_by'         => $ambulance->added_by,
                    'created_at'       => $ambulance->created_at,
                    'updated_by'       => $ambulance->updated_by,
                    'updated_at'       => $ambulance->updated_at,
                ]
            ));
        }

        if (!empty($t['clear_driver'])) {
            $req->driver_id     = null;
            $req->ambulance_id  = null;
            $req->notes         = null;
            $req->dispatched_at = null;
        }

        $adminPayload = [
            'id'           => $req->id,
            'status'       => $req->status,
            'action'       => $action,
            'completed_at' => $req->completed_at?->format('d M Y, H:i A'),
            'accepted_lat' => $req->accepted_lat ? (float) $req->accepted_lat : null,
            'accepted_lng' => $req->accepted_lng ? (float) $req->accepted_lng : null,
        ];
        
        $driverPayload = [
            'id'           => $req->id,
            'status'       => $req->status,
            'action'       => $action,
            'completed_at' => $req->completed_at?->format('d M Y, h:i A'),
        ];

        if ($action === 'complete') {
            $adminPayload['rreb_id']        = $req->rreb_id;
            $adminPayload['type']           = $req->type;
            $adminPayload['hospital_name']  = $req->hospital_name;
            $adminPayload['pickup_address'] = $req->pickup_address;
            $adminPayload['driver_name']    = $driver->name;
            $adminPayload['driver_phone']   = $driver->phone;
            $adminPayload['ambulance_no']   = $ambulance->vehicle_number;
            $adminPayload['ambulance_type'] = match((string)($ambulance->type ?? '')) {
                '1' => 'BLS — Basic Life Support',
                '2' => 'ALS — Advanced Life Support',
                '3' => 'CCT — Critical Care Transport',
                '4' => 'Neonatal',
                '5' => 'AIR Ambulance',
                default => $ambulance->type ?? null,
            };
            $adminPayload['user_name']      = $req->user?->details?->first_name ?? 'Guest';
            $adminPayload['mobile_no']      = $req->mobile_no;
            $adminPayload['notes']          = $req->notes;
            $adminPayload['dispatched_at']  = $req->dispatched_at?->format('l d M Y \a\t h:i A');
            $adminPayload['created_at']     = $req->created_at->format('l d M Y \a\t h:i A');
            $adminPayload['pickup_lat']     = $req->pickup_lat    ? (float) $req->pickup_lat    : null;
            $adminPayload['pickup_lng']     = $req->pickup_lng    ? (float) $req->pickup_lng    : null;
            $adminPayload['hospital_lat']   = $req->hospital_lat  ? (float) $req->hospital_lat  : null;
            $adminPayload['hospital_lng']   = $req->hospital_lng  ? (float) $req->hospital_lng  : null;

            $driverPayload['rreb_id']        = $req->rreb_id;
            $driverPayload['type']           = $req->type;
            $driverPayload['pickup_address'] = $req->pickup_address;
            $driverPayload['hospital_name']  = $req->hospital_name;
            $driverPayload['ambulance_no']   = $req->ambulance?->vehicle_number;
            $driverPayload['mobile_no']      = $req->mobile_no;
            $driverPayload['notes']          = $req->notes;
            $driverPayload['dispatched_at']  = $req->dispatched_at?->format('d M Y, h:i A');
            $driverPayload['created_at']     = $req->created_at->format('d M Y, h:i A');
            $driverPayload['accepted_lat']   = $req->accepted_lat  ? (float) $req->accepted_lat  : null;
            $driverPayload['accepted_lng']   = $req->accepted_lng  ? (float) $req->accepted_lng  : null;
            $driverPayload['pickup_lat']     = $req->pickup_lat    ? (float) $req->pickup_lat    : null;
            $driverPayload['pickup_lng']     = $req->pickup_lng    ? (float) $req->pickup_lng    : null;
            $driverPayload['hospital_lat']   = $req->hospital_lat  ? (float) $req->hospital_lat  : null;
            $driverPayload['hospital_lng']   = $req->hospital_lng  ? (float) $req->hospital_lng  : null;
        }
        
        event(new \App\Events\Emergency('admin-dashboard', 'emergency-request-status-changed', $adminPayload));
        event(new \App\Events\Emergency('driver.' . $driver->id, 'emergency-request-status-changed', $driverPayload));
        event(new \App\Events\Emergency('emergency.' . $req->id, 'emergency-request-status-changed', $userPayload));

        return response()->json(['success' => true, 'status' => $req->status]);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
