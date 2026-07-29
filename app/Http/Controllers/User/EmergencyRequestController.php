<?php

namespace App\Http\Controllers\User;

use App\Events\Emergency;
use App\Http\Controllers\Controller;
use App\Mail\EmergencyRequestConfirmationMail;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmergencyRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'hospital_name'   => 'required|string|max:300',
            'hospital_lat'    => 'required|numeric',
            'hospital_lng'    => 'required|numeric',
            'mobile_no'       => 'required|regex:/^03[0-9]{9}$/',
            'emergency_email' => 'required|email|max:100',
            'pickup_address'  => 'required|string|max:300',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'type'            => 'required|in:1,2',
        ], [
            'hospital_name.required'  => 'Hospital name is required. Please search and select one from suggestions.',
            'hospital_lat.required'   => 'Please select a hospital from the suggestions list.',
            'hospital_lng.required'   => 'Please select a hospital from the suggestions list.',
            'mobile_no.required'      => 'Mobile number is required.',
            'mobile_no.regex'         => 'Enter a valid Pakistani number (03XXXXXXXXX).',
            'pickup_address.required' => 'Pickup address is required.',
            'latitude.required'       => 'Please select your pickup location from the suggestions or use "Use my current location".',
            'longitude.required'      => 'Please select your pickup location from the suggestions or use "Use my current location".',
            'type.required'           => 'Please select emergency type.',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::guard('users')->id();

            $req = EmergencyRequest::create([
                'user_id'        => $userId,
                'hospital_name'  => $request->hospital_name,
                'hospital_lat'   => $request->hospital_lat,
                'hospital_lng'   => $request->hospital_lng,
                'mobile_no'      => $request->mobile_no,
                'email'          => $request->emergency_email,
                'pickup_address' => $request->pickup_address,
                'pickup_lat'     => $request->latitude,
                'pickup_lng'     => $request->longitude,
                'type'           => $request->type,
                'status'         => '1',
            ]);

            DB::commit();

            event(new Emergency('private:user.bookings.' . $userId, 'new-booking-created', [
                'id'             => $req->id,
                'rreb_id'        => $req->rreb_id,
                'type'           => $req->type,
                'pickup_address' => $req->pickup_address,
                'status'         => $req->status,
                'created_at'     => $req->created_at->format('d M Y'),
                'tracking_url'   => route('tracking', $req->id),
            ]));
            
            event(new Emergency('admin-dashboard', 'emergency-request-created', [
                'action'         => 'created',
                'id'             => $req->id,
                'rreb_id'        => $req->rreb_id,
                'user_name'      => $req->user?->details?->first_name ?? 'Guest',
                'mobile_no'      => $req->mobile_no,
                'hospital_name'  => $req->hospital_name,
                'pickup_address' => $req->pickup_address,
                'type'           => $req->type,
                'status'         => $req->status,
                'ambulance'      => null,
                'driver'         => null,
                'created_at'     => $req->created_at->format('d M, H:i'),
            ]));
            
            try {
                Mail::to($req->email)->send(new EmergencyRequestConfirmationMail($req));
            } catch (\Throwable $e) {
                Log::error('ER confirmation email failed', [
                    'rreb_id' => $req->rreb_id,
                    'email'   => $req->email,
                    'error'   => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Emergency request submitted! Our team will assign an ambulance shortly.',
                'request_id' => $req->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
