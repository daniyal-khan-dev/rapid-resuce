<?php

namespace App\Http\Controllers\Admin;

use App\Events\AdminContentUpdate;
use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminManagerController extends Controller
{

    public function index()
    {
        $admins = Admin::orderBy('id', 'ASC')->get();
        return view('admin.pages.admins', compact('admins'));
    }

    public function checkUsername(Request $request): JsonResponse
    {
        $username  = trim($request->input('username', ''));
        $excludeId = $request->input('exclude_id');

        if (!$username) {
            return response()->json(['available' => false, 'message' => 'Username is required.']);
        }

        $query = Admin::where('username', $username);
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
            'admin_name'     => 'required|string|max:20',
            'admin_username' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_.]+$/', 'unique:admins,username'],
            'admin_email'    => 'required|email|unique:admins,email',
            'admin_password' => ['required', 'string', 'min:7', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_\-])[A-Za-z\d@$!%*#?&^_\-]{7,}$/'],
            'admin_status'   => 'required|in:1,2',
        ], [
            'admin_name.required' => 'Admin name is required.',
            'admin_name.string'   => 'Admin name must be valid text.',
            'admin_name.max'      => 'Admin name cannot exceed 20 characters.',
            'admin_username.required' => 'Username is required.',
            'admin_username.string'   => 'Username must be valid text.',
            'admin_username.max'      => 'Username cannot exceed 30 characters.',
            'admin_username.regex'    => 'Username may only contain lowercase letters, numbers, underscore (_) and dot (.).',
            'admin_username.unique'   => 'This username is already taken.',
            'admin_email.required' => 'Email address is required.',
            'admin_email.email'    => 'Please enter a valid email address.',
            'admin_email.unique'   => 'This email is already registered.',
            'admin_password.required'  => 'Password is required.',
            'admin_password.string'    => 'Password must be valid text.',
            'admin_password.min'       => 'Password must be at least 7 characters long.',
            'admin_password.confirmed'  => 'Password confirmation does not match.',
            'admin_password.regex'     => 'Password must include at least one letter, one number, and one special character (@$!%*#?&^_-).',
            'admin_status.required' => 'Admin status is required.',
            'admin_status.in'       => 'Selected admin status is invalid.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $admin = Admin::create([
                'name'     => $request->admin_name,
                'username' => $request->admin_username,
                'email'    => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'status'   => $request->admin_status,
                'added_by' => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Added admin: {$admin->email}");
            event(new AdminContentUpdate(
                entity: 'admins',
                action: 'created',
                data: [
                    'id'       => $admin->id,
                    'name'     => $admin->name,
                    'username' => $admin->username,
                    'email'    => $admin->email,
                    'status'   => $admin->status,
                    'added_by' => $admin->added_by,
                    'created_at' => $admin->created_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Admin added.', 'item' => $admin]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'admin_name'     => 'required|string|max:20',
            'admin_username' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_.]+$/', Rule::unique('admins', 'username')->ignore($id)],
            'admin_email'    => ['required', 'email', Rule::unique('admins', 'email')->ignore($id)],
            'admin_password' => ['nullable', 'string', 'min:7', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_\-])[A-Za-z\d@$!%*#?&^_\-]{7,}$/'],
            'admin_status'   => 'required|in:1,2',
        ], [
            'admin_name.required' => 'Admin name is required.',
            'admin_name.string'   => 'Admin name must be valid text.',
            'admin_name.max'      => 'Admin name cannot exceed 20 characters.',
            'admin_username.required' => 'Username is required.',
            'admin_username.string'   => 'Username must be valid text.',
            'admin_username.max'      => 'Username cannot exceed 30 characters.',
            'admin_username.regex'    => 'Username may only contain lowercase letters, numbers, underscore (_) and dot (.).',
            'admin_username.unique'   => 'This username is already taken.',
            'admin_email.required' => 'Email address is required.',
            'admin_email.email'    => 'Please enter a valid email address.',
            'admin_email.unique'   => 'This email is already registered.',
            'admin_password.string'     => 'Password must be valid text.',
            'admin_password.min'        => 'Password must be at least 7 characters long.',
            'admin_password.confirmed'   => 'Password confirmation does not match.',
            'admin_password.regex'      => 'Password must include at least one letter, one number, and one special character (@$!%*#?&^_-).',
            'admin_status.required' => 'Admin status is required.',
            'admin_status.in'       => 'Selected admin status is invalid.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $admin = Admin::findOrFail($id);
            $data = [
                'name'       => $request->admin_name,
                'username'   => $request->admin_username,
                'email'      => $request->admin_email,
                'status'     => $request->admin_status,
                'updated_by' => $actor->username,
            ];

            if ($request->filled('admin_password')) {
                $data['password'] = Hash::make($request->admin_password);
            }
            $admin->update($data);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Updated admin: {$admin->email}");
            event(new AdminContentUpdate(
                entity: 'admins',
                action: 'updated',
                data: [
                    'id'       => $admin->id,
                    'name'     => $admin->name,
                    'username' => $admin->username,
                    'email'    => $admin->email,
                    'status'   => $admin->status,
                    'added_by' => $admin->added_by,
                    'created_at' => $admin->created_at,
                    'updated_by' => $admin->updated_by,
                    'updated_at' => $admin->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Admin updated.', 'item' => $admin]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        $actor = Auth::guard('admin')->user();
        if ((int) $actor->id === (int) $id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }

        $admin = Admin::findOrFail($id);
        $itemId = $admin->id;
        $email = $admin->email;
        $admin->delete();
        logHistory($actor->username, request()->ip(), "Deleted admin: {$email}");
        event(new AdminContentUpdate(
            entity: 'admins',
            action: 'deleted',
            data: [
                'id' => $itemId,
            ]
        ));
        return response()->json(['success' => true, 'message' => 'Admin account removed.']);
    }
}
