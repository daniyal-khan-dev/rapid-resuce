<?php

namespace App\Http\Controllers\Admin;

use App\Events\Emergency;
use App\Http\Controllers\Controller;
use App\Models\Admin\Ambulance;
use App\Models\Driver\Driver;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmergencyController extends Controller
{
    public function index()
    {
        $requests   = EmergencyRequest::with(['user.details', 'ambulance', 'driver'])->whereNotIn('status', ['6', '7'])->latest()->paginate(20);
        $ambulances = Ambulance::where('status', '1')->get();
        $drivers    = Driver::where('status', 1)->where('availability', 1)->get();
        return view('admin.pages.emergency', compact('requests', 'ambulances', 'drivers'));
    }

    public function pastRides(Request $request)
    {
        if ($request->filled('q')) {
            try {
                $decoded = json_decode(base64_decode((string) $request->q), true);
                if (is_array($decoded)) {
                    $request->merge($decoded);
                }
            } catch (\Throwable $e) {}
        }

        $query = EmergencyRequest::with(['user.details', 'ambulance', 'driver'])
            ->whereIn('status', ['6', '7']);

        if ($request->filled('status') && in_array($request->status, ['6', '7'])) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rreb_id',         'like', "%$s%")
                  ->orWhere('hospital_name',  'like', "%$s%")
                  ->orWhere('pickup_address', 'like', "%$s%")
                  ->orWhere('mobile_no',      'like', "%$s%");
            });
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('ambulance_id')) {
            $query->where('ambulance_id', $request->ambulance_id);
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

        $rides = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'completed' => EmergencyRequest::where('status', '6')->count(),
            'cancelled' => EmergencyRequest::where('status', '7')->count(),
        ];
        $allDrivers    = Driver::orderBy('name')->get(['id', 'name', 'phone']);
        $allAmbulances = Ambulance::orderBy('vehicle_number')->get(['id', 'vehicle_number', 'type']);

        return view('admin.pages.past_rides', compact('rides', 'stats', 'allDrivers', 'allAmbulances'));
    }

    public function show($id): JsonResponse
    {
        $req     = EmergencyRequest::with(['user.details', 'ambulance', 'driver'])->findOrFail($id);
        $drivers = Driver::where('status', 1)->where('availability', 1)->get();

        $firstName = $req->user?->details?->first_name ?? '';
        $lastName  = $req->user?->details?->last_name  ?? '';
        $userName  = trim($firstName . ' ' . $lastName) ?: 'Guest';

        return response()->json([
            'success'   => true,
            'request'   => $req,
            'drivers'   => $drivers,
            'user_name' => $userName,
        ]);
    }


    public function dispatch(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'driver_id'    => 'required|exists:drivers,id',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $req = EmergencyRequest::findOrFail($id);

            if (!in_array($req->status, ['1'])) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Only Pending requests can be dispatched.'], 422);
            }

            $driver = Driver::where('id', $validated['driver_id'])->where('status', '1')->where('availability', '1')->first();

            if (!$driver) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Selected driver is no longer available. Please pick another driver.'], 422);
            }

            $ambulance = Ambulance::where('driver_id', $driver->id)->where('status', '1')->first();

            if (!$ambulance) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Selected driver has no available ambulance assigned.'], 422);
            }

            $req->ambulance_id     = $ambulance->id;
            $req->driver_id     = $driver->id;
            $req->notes         = $validated['notes'] ?? null;
            $req->status        = '8';
            $req->dispatched_at = now();
            $req->save();

            DB::commit();

            $admin = Auth::guard('admin')->user();
            $loaded = $req->load(['driver', 'ambulance']);
            logHistory($admin->username, $request->ip(), "Sent dispatch request for request #{$req->id} — ambulance ID: {$request->ambulance_id}, driver ID: {$request->driver_id}");
            // Broadcast to the admin dashboard
            event(new Emergency('admin-dashboard', 'emergency-request-dispatched', [
                'id'        => $loaded->id,
                'status'    => $loaded->status,
                'driver'    => $loaded->driver?->name,
                'ambulance'      => $loaded->ambulance?->vehicle_number,
            ]));
            event(new Emergency('admin-dashboard', 'emergency-request-status-changed', [
                'id'        => $loaded->id,
                'status'    => $loaded->status,
            ]));

            // Broadcast to the assigned driver's personal channel (public, driver-id-scoped)
            event(new Emergency('driver.' . $driver->id, 'emergency-request-dispatched', [
                'id'             => $loaded->id,
                'rreb_id'        => $loaded->rreb_id,
                'type'           => $loaded->type,
                'pickup_address' => $loaded->pickup_address,
                'hospital_name'  => $loaded->hospital_name,
                'mobile_no'      => $loaded->mobile_no,
                'ambulance'      => $loaded->ambulance?->vehicle_number,
                'status'         => $loaded->status,
                'dispatched_at'  => $loaded->dispatched_at?->format('d M, H:i'),
                'created_at'     => $loaded->created_at->format('d M, H:i'),
            ]));
            return response()->json([
                'success' => true,
                'message' => 'Dispatch request sent. Awaiting driver acceptance.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function nearbyDrivers(Request $request): JsonResponse
    {
        $lat    = (float) $request->get('lat', 0);
        $lng    = (float) $request->get('lng', 0);
        $radius = (float) $request->get('radius', 30);

        $drivers = Driver::where('status', '1')
            ->whereNotNull('lat')->whereNotNull('lng')
            ->where('lat', '!=', 0)->where('lng', '!=', 0)
            ->get(['id', 'name', 'phone', 'lat', 'lng']);

        $result = $drivers->map(function ($d) use ($lat, $lng) {
            $d->distance_km = round($this->haversine($lat, $lng, (float)$d->lat, (float)$d->lng), 2);
            return $d;
        })
        ->filter(fn($d) => $d->distance_km <= $radius)
        ->sortBy('distance_km')
        ->values();

        return response()->json(['success' => true, 'drivers' => $result]);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function delete($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $req = EmergencyRequest::findOrFail($id);

            if ($req->status !== '1') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Only pending rides can be deleted.'], 422);
            }

            if ($req->ambulance_id) {
                Ambulance::where('id', $req->ambulance_id)->update(['status' => '1']);
            }

            if ($req->driver_id) {
                Driver::where('id', $req->driver_id)->update(['status' => '1']);
            }

            $admin = Auth::guard('admin')->user();
            logHistory($admin->username, request()->ip(), "Deleted emergency request #{$req->id}");

            $deletedId = $req->id;
            $req->delete();

            DB::commit();

            event(new Emergency('admin-dashboard', 'emergency-request-deleted', [
                'action' => 'deleted',
                'id'     => $deletedId,
            ]));

            /* Notify the user's booking/tracking channel so the My Bookings
               table removes the row and the Tracking page redirects. */
            event(new Emergency('emergency.' . $deletedId, 'emergency-request-deleted', [
                'action' => 'deleted',
                'id'     => $deletedId,
            ]));
            
            return response()->json(['success' => true, 'message' => 'Request deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
