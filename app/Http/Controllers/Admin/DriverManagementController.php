<?php

namespace App\Http\Controllers\Admin;

use App\Events\DriverUpdated;
use App\Http\Controllers\Controller;
use App\Models\Admin\Ambulance;
use App\Models\Driver\Driver;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DriverManagementController extends Controller
{
    public function index()
    {
        $drivers = Driver::withCount([
            'emergencyRequests as total_jobs',
            'emergencyRequests as completed_jobs' => fn ($q) => $q->where('status', '6'),
        ])->latest()->get();
        return view('admin.pages.drivers', compact('drivers'));
    }
    
    public function checkUsername(Request $request): JsonResponse
    {
        $username  = trim($request->input('username', ''));
        $excludeId = $request->input('exclude_id');

        if (!$username) {
            return response()->json(['available' => false, 'message' => 'Username is required.']);
        }

        $query = Driver::where('username', $username);
        if ($excludeId) {
            $query->where('id', '!=', (int) $excludeId);
        }

        $taken = $query->exists();

        return response()->json([
            'available' => !$taken,
            'message'   => $taken ? 'Username is already taken.' : 'Username is available.',
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'username'   => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_.]+$/', 'unique:drivers,username'],
            'email'      => 'required|email|unique:drivers,email',
            'phone'      => 'required|regex:/^03[0-9]{9}$/',
            'license_no' => 'required|string|max:30',
            'password'   => 'required|min:6',
            'status'     => 'required|in:1,2',
            'photo'      => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required'       => 'Driver name is required.',
            'username.required'   => 'Username is required.',
            'username.regex'      => 'Username may only contain lowercase letters (a-z), numbers, underscore (_) and dot (.).',
            'username.unique'     => 'This username is already taken.',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already registered.',
            'phone.required'      => 'Phone number is required.',
            'phone.regex'         => 'Enter a valid Pakistani number (03XXXXXXXXX).',
            'license_no.required' => 'License number is required.',
            'password.required'   => 'Password is required.',
            'password.min'        => 'Password must be at least 6 characters long.',
            'status.required'     => 'Please select a driver status.',
            'status.in'           => 'The selected driver status is invalid.',
            'photo.required'      => 'Driver photo is required.',
            'photo.image'         => 'Please upload a valid image.',
            'photo.mimes'         => 'Photo must be a JPG, JPEG, PNG, or WebP file.',
            'photo.max'           => 'Photo size cannot exceed 2 MB.',
        ]);
            
        DB::beginTransaction();
        try {
            $file      = $request->file('photo');
            $photoName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/driver/img'), $photoName);
            $admin = Auth::guard('admin')->user();

            $driver = Driver::create([
                'name'       => $request->name,
                'username'   => $request->username,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'license_no' => $request->license_no,
                'photo'      => $photoName,
                'status'     => $request->status,
                'added_by'   => $admin->username,
            ]);

            DB::commit();
            
            logHistory($admin->username, $request->ip(), "Added driver: {$driver->name} ({$driver->email})");
            event(new DriverUpdated(
                entity: 'driverAdminUpdated',
                action: 'created',
                data: [
                    'id' => $driver->id,
                    'name'         => $driver->name,
                    'username'     => $driver->username,
                    'email'        => $driver->email,
                    'phone'        => $driver->phone,
                    'license_no'   => $driver->license_no,
                    'photo'        => $driver->photo,
                    'photo_url'    => ($driver->photo && $driver->photo !== 'default.jpg') ? asset('assets/driver/img/' . $driver->photo) : null,
                    'status'       => $driver->status,       
                    'availability' => 3,     
                    'total_jobs'   => 0,
                    'completed_jobs' => 0,
                    'added_by'     => $driver->added_by,
                    'created_at'   => $driver->created_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Driver added.', 'driver' => $driver]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'username'   => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_.]+$/', Rule::unique('drivers', 'username')->ignore($id)],
            'email'      => ['required', 'email', Rule::unique('drivers', 'email')->ignore($id)],
            'phone'      => 'required|regex:/^03[0-9]{9}$/',
            'license_no' => 'required|string|max:30',
            'status'     => 'required|in:1,2',
            'password'   => 'nullable|min:6',
            'photo'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required'       => 'Driver name is required.',
            'name.max'            => 'Driver name cannot exceed 50 characters.',
            'username.required'   => 'Username is required.',
            'username.max'        => 'Username cannot exceed 50 characters.',
            'username.regex'      => 'Username may only contain lowercase letters (a-z), numbers, underscore (_) and dot (.).',
            'username.unique'     => 'This username is already taken.',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already registered.',
            'phone.required'      => 'Phone number is required.',
            'phone.regex'         => 'Enter a valid Pakistani number (03XXXXXXXXX).',
            'license_no.required' => 'License number is required.',
            'license_no.max'      => 'License number cannot exceed 30 characters.',
            'status.required'     => 'Please select a driver status.',
            'status.in'           => 'The selected driver status is invalid.',
            'password.min'        => 'Password must be at least 6 characters long.',
            'photo.image'         => 'Please upload a valid image.',
            'photo.mimes'         => 'Photo must be a JPG, JPEG, PNG, or WebP file.',
            'photo.max'           => 'Photo size cannot exceed 2 MB.',
        ]);
            
        DB::beginTransaction();
        try {
            $driver   = Driver::findOrFail($id);
            $photoUrl = null;
            $admin = Auth::guard('admin')->user();

            // Block availability change while driver is on an active ride
            if ($driver->availability == '2') {
                $hasActiveRide = EmergencyRequest::where('driver_id', $driver->id)->whereNotIn('status', ['6', '7'])->exists();
                if ($hasActiveRide) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'This driver is currently on an active ride and cannot update.',
                    ], 422);
                }
            }

            if ($request->hasFile('photo')) {
                // Delete old photo
                if ($driver->photo && $driver->photo !== 'default.jpg') {
                    $oldPath = public_path('assets/driver/img/' . $driver->photo);
            
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                // Upload new photo
                $file = $request->file('photo');
                $photoName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/driver/img'), $photoName);
                $driver->photo = $photoName;
                $photoUrl = asset('assets/driver/img/' . $photoName);
            }

            $driver->name       = $request->name;
            $driver->username   = $request->username;
            $driver->email      = $request->email;
            $driver->phone      = $request->phone;
            $driver->license_no = $request->license_no;
            $driver->status     = $request->status;
            $driver->updated_by   = $admin->username;
            if ($request->filled('password')) {
                $driver->password = Hash::make($request->password);
            }
            $driver->save();
            DB::commit();

            $drivers = Driver::withCount([
                'emergencyRequests as total_jobs',
                'emergencyRequests as completed_jobs' => fn ($q) => $q->where('status', '6'),
            ])->find($driver->id);

            logHistory($admin->username, $request->ip(), "Updated driver: {$driver->name} — status: {$driver->availability}");
            event(new DriverUpdated(
                entity: 'driverAdminUpdated',
                action: 'updated',
                data: [
                    'id'           => $drivers->id,
                    'name'         => $drivers->name,
                    'username'     => $drivers->username,
                    'email'        => $drivers->email,
                    'phone'        => $drivers->phone,
                    'license_no'   => $drivers->license_no,
                    'photo'        => $drivers->photo,
                    'photo_url'    => ($drivers->photo && $drivers->photo !== 'default.jpg') ? asset('assets/driver/img/' . $drivers->photo) : null,
                    'status'       => $drivers->status,       
                    'availability' => $drivers->availability,     
                    'lat'          => $drivers->lat !== null ? (float) $drivers->lat : null,
                    'lng'          => $drivers->lng !== null ? (float) $drivers->lng : null,
                    'total_jobs'   => $driver->total_jobs,
                    'completed_jobs' => $driver->completed_jobs,
                    'added_by'     => $drivers->added_by,
                    'created_at'   => $drivers->created_at,
                    'updated_by'   => $drivers->updated_by,
                    'updated_at'   => $drivers->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Driver updated.', 'driver' => $driver]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $driver = Driver::findOrFail($id);

            // Block deletion while driver has an active ride or is On Duty
            $hasActiveRide = EmergencyRequest::where('driver_id', $driver->id)->whereNotIn('status', ['6', '7'])->exists();
            if ($hasActiveRide || $driver->availability === '2') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Driver cannot be deleted while assigned to an active ride.',
                ], 422);
            }

            // Block deletion when driver is assigned to an ambulance
            $isAssignedToAmbulance = Ambulance::where('driver_id', $driver->id)->exists();
            if ($isAssignedToAmbulance) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This driver is assigned to an ambulance and cannot be deleted.',
                ], 422);
            }

            if ($driver->photo && $driver->photo !== 'default.jpg') {
                $photoPath = public_path('assets/driver/img/' . $driver->photo);
            
                if (file_exists($photoPath)) {
                    @unlink($photoPath);
                }
            }
            $driverName = $driver->name;
            $driverID = $driver->id;
            $admin = Auth::guard('admin')->user();
            
            $driver->delete();
            
            DB::commit();
            logHistory($admin->username, request()->ip(), "Deleted driver: {$driverName}");
            event(new DriverUpdated(
                entity: 'driverAdminUpdated',
                action: 'deleted',
                data: [
                    'id' => $driverID,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Driver deleted.', 'driver' => $driver]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
