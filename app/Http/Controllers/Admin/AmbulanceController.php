<?php

namespace App\Http\Controllers\Admin;

use App\Events\AdminContentUpdate;
use App\Http\Controllers\Controller;
use App\Models\Admin\Ambulance;
use App\Models\Driver\Driver;
use App\Models\User\EmergencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AmbulanceController extends Controller
{
    public function index()
    {
        $ambulances = Ambulance::with('driver')->orderBy('id', 'ASC')->get();
        $drivers    = Driver::whereStatus(1)->orderBy('name')->get(['id', 'name', 'phone']);
        return view('admin.pages.ambulances', compact('ambulances', 'drivers'));
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_number'   => 'required|string|max:20|unique:ambulances,vehicle_number',
            'type'             => 'required|in:1,2,3,4,5',
            'equipment_level'  => 'required|in:1,2',
            'status'           => 'required|in:1,2,3,4',
            'driver_id'        => 'nullable|exists:drivers,id',
            'notes'            => 'nullable|string|max:500',
            'card_title'       => 'required|string|max:20',
            'card_description' => 'required|string|max:500',
            'card_image'       => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'card_features'    => 'required|string|max:50',
            'card_rating'      => 'required|numeric|min:0|max:5',
            'card_trips'       => 'nullable|integer|min:0',
        ], [
            // vehicle_number
            'vehicle_number.required' => 'Vehicle number is required.',
            'vehicle_number.string'   => 'Vehicle number must be a valid text.',
            'vehicle_number.max'      => 'Vehicle number must not exceed 20 characters.',
            'vehicle_number.unique'   => 'This vehicle number is already registered.',
            'type.required' => 'Vehicle type is required.',
            'type.in'       => 'Selected vehicle type is invalid.',
            'equipment_level.required' => 'Equipment level is required.',
            'equipment_level.in'       => 'Invalid equipment level selected.',
            'status.required' => 'Status is required.',
            'status.in'       => 'Selected status is invalid.',
            'driver_id.exists' => 'Selected driver does not exist.',
            'notes.string' => 'Notes must be a valid text.',
            'notes.max'    => 'Notes cannot exceed 500 characters.',
            'card_title.required' => 'Card title is required.',
            'card_title.max'      => 'Card title cannot exceed 20 characters.',
            'card_description.required' => 'Card description is required.',
            'card_description.max'      => 'Card description cannot exceed 500 characters.',
            'card_image.required' => 'Card image is required.',
            'card_image.image'    => 'Uploaded file must be an image.',
            'card_image.mimes'    => 'Image must be jpg, jpeg, png, or webp format.',
            'card_image.max'      => 'Image size must not exceed 2MB.',
            'card_features.required' => 'Card features are required.',
            'card_features.max'      => 'Card features cannot exceed 50 characters.',
            'card_rating.required' => 'Card rating is required.',
            'card_rating.numeric'  => 'Card rating must be a number.',
            'card_rating.min'      => 'Rating cannot be less than 0.',
            'card_rating.max'      => 'Rating cannot be greater than 5.',

            'card_trips.integer' => 'Trips must be a valid number.',
            'card_trips.min'     => 'Trips cannot be negative.',
        ]);
            
        // One ambulance = one driver: check if selected driver is already assigned
        if ($request->filled('driver_id')) {
            $conflict = Ambulance::where('driver_id', $request->driver_id)->first();
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver "' . optional($conflict->driver)->name . '" is already assigned to ambulance ' . $conflict->vehicle_number . '. Please unassign them first.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $admin = Auth::guard('admin')->user();

            $data = $request->only([
                'vehicle_number', 'type', 'equipment_level', 'status', 'driver_id',
                'notes', 'card_title', 'card_description',
                'card_features', 'card_rating', 'card_trips',
            ]);

            $data['added_by'] = $admin->username;

            $folderPath = public_path('assets/admin/img/fleet');
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            if ($request->hasFile('card_image')) {
                $file = $request->file('card_image');
                $name = time() . '_' . $file->getClientOriginalName();
                $file->move($folderPath, $name);
                $data['card_image'] = $name;
            }

            $ambulance = Ambulance::create($data);

            DB::commit();
            $ambulance->load('driver');
            logHistory($admin->username, $request->ip(), "Added ambulance: {$ambulance->vehicle_number} ({$ambulance->type})");
            event(new AdminContentUpdate(
                entity: 'ambulance',
                action: 'created',
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
            return response()->json(['success' => true, 'message' => 'Ambulance added.', 'ambulance' => $ambulance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'vehicle_number'   => 'required|string|max:20|unique:ambulances,vehicle_number,' . $id,
            'type'             => 'required|in:1,2,3,4,5',
            'equipment_level'  => 'required|in:1,2',
            'status'           => 'required|in:1,2,3,4',
            'driver_id'        => 'nullable|exists:drivers,id',
            'notes'            => 'nullable|string|max:500',
            'card_title'       => 'required|string|max:20',
            'card_description' => 'required|string|max:500',
            'card_image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'card_features'    => 'required|string|max:50',
            'card_rating'      => 'required|numeric|min:0|max:5',
            'card_trips'       => 'nullable|integer|min:0',
        ], [
            'vehicle_number.required' => 'Vehicle number is required.',
            'vehicle_number.string'   => 'Vehicle number must be valid text.',
            'vehicle_number.max'      => 'Vehicle number cannot exceed 20 characters.',
            'vehicle_number.unique'   => 'This vehicle number is already assigned to another ambulance.',
            'type.required' => 'Please select an ambulance type.',
            'type.in'       => 'The selected ambulance type is invalid.',
            'equipment_level.required' => 'Please select an equipment level.',
            'equipment_level.in'       => 'The selected equipment level is invalid.',
            'status.required' => 'Please select a status.',
            'status.in'       => 'The selected status is invalid.',
            'driver_id.exists' => 'The selected driver does not exist.',
            'notes.string' => 'Notes must be valid text.',
            'notes.max'    => 'Notes cannot exceed 500 characters.',
            'card_title.required' => 'Card title is required.',
            'card_title.string'   => 'Card title must be valid text.',
            'card_title.max'      => 'Card title cannot exceed 20 characters.',
            'card_description.required' => 'Card description is required.',
            'card_description.string'   => 'Card description must be valid text.',
            'card_description.max'      => 'Card description cannot exceed 500 characters.',
            'card_image.image' => 'Please upload a valid image.',
            'card_image.mimes' => 'Card image must be a JPG, JPEG, PNG, or WebP file.',
            'card_image.max'   => 'Card image size cannot exceed 2 MB.',
            'card_features.required' => 'Card features are required.',
            'card_features.string'   => 'Card features must be valid text.',
            'card_features.max'      => 'Card features cannot exceed 50 characters.',
            'card_rating.required' => 'Card rating is required.',
            'card_rating.numeric'  => 'Card rating must be a number.',
            'card_rating.min'      => 'Card rating cannot be less than 0.',
            'card_rating.max'      => 'Card rating cannot be greater than 5.',
            'card_trips.integer' => 'Total trips must be a whole number.',
            'card_trips.min'     => 'Total trips cannot be negative.',
        ]);
            
        // One ambulance = one driver: check if selected driver is already assigned to a DIFFERENT ambulance
        if ($request->filled('driver_id')) {
            $conflict = Ambulance::where('driver_id', $request->driver_id)->where('id', '!=', $id)->first();
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver "' . optional($conflict->driver)->name . '" is already assigned to ambulance ' . $conflict->vehicle_number . '. Please unassign them first.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $admin     = Auth::guard('admin')->user();
            $ambulance = Ambulance::findOrFail($id);
            $oldDriverId = $ambulance->driver_id;

            // Block driver / status changes while ambulance is on an active ride
            $hasActiveRide = EmergencyRequest::where('ambulance_id', $ambulance->id)->whereNotIn('status', ['6', '7'])->exists();

            if ($hasActiveRide) {
                $incomingDriver = (string) $request->input('driver_id', '');
                $incomingStatus = (string) $request->input('status', '');

                if ($incomingDriver !== (string) $ambulance->driver_id) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'This ambulance is currently assigned to an active ride and cannot be modified.',
                    ], 422);
                }

                if ($incomingStatus !== (string) $ambulance->status) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'This ambulance is currently assigned to an active ride and cannot be modified.',
                    ], 422);
                }
            }

            $data = $request->only([
                'vehicle_number', 'type', 'equipment_level', 'status', 'driver_id',
                'notes', 'card_title', 'card_description',
                'card_features', 'card_rating', 'card_trips',
            ]);

            $data['updated_by'] = $admin->username;

            if ($request->hasFile('card_image')) {
                $folderPath = public_path('assets/admin/img/fleet');
                if (!file_exists($folderPath)) {
                    mkdir($folderPath, 0777, true);
                }

                // Delete old image
                if ($ambulance->card_image) { $oldImagePath = $folderPath . '/' . $ambulance->card_image;
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }

                $file = $request->file('card_image');
                $name = time() . '_' . $file->getClientOriginalName();
                $file->move($folderPath, $name);
                $data['card_image'] = $name;
            }

            $ambulance->update($data);
            $ambulance->refresh();
            $ambulance->load('driver');

            DB::commit();
            logHistory($admin->username, $request->ip(), "Updated ambulance: {$ambulance->vehicle_number} — status: {$ambulance->status}");
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
            return response()->json(['success' => true, 'message' => 'Ambulance updated.', 'ambulance' => $ambulance]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $ambulance = Ambulance::findOrFail($id);

            // Block deletion while ambulance is on an active ride or status is On Job
            $hasActiveRide = EmergencyRequest::where('ambulance_id', $ambulance->id)->whereNotIn('status', ['6', '7'])->exists();

            if ($hasActiveRide || $ambulance->status === '2') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ambulance cannot be deleted while assigned to an active ride.',
                ], 422);
            }

            $vehicleNo = $ambulance->vehicle_number;
            $oldPath = base_path('../assets/admin/img/fleet/' . $ambulance->card_image);
            if ($ambulance->card_image && file_exists($oldPath)) {
                @unlink($oldPath);
            }

            $ambulance->delete();
            $admin = Auth::guard('admin')->user();
            logHistory($admin->username, request()->ip(), "Deleted ambulance: {$vehicleNo}");
            DB::commit();
            event(new AdminContentUpdate(
                entity: 'ambulance',
                action: 'deleted',
                data: ['id' => $id]
            ));
            return response()->json(['success' => true, 'message' => 'Ambulance removed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
