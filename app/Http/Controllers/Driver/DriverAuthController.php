<?php

namespace App\Http\Controllers\Driver;

use App\Events\DriverUpdated;
use App\Http\Controllers\Controller;
use App\Models\Driver\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DriverAuthController extends Controller
{
    private function noCache($response)
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache')->header('Expires', '0');
    }

    public function login()
    {
        if (Auth::guard('driver')->check()) {
            return redirect()->route('driver.dashboard');
        }
        if (Auth::guard('users')->check()) {
            return redirect()->route('home');
        }
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return $this->noCache(response()->view('driver.auth.login'));
    }

    public function loginSubmit(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 6 characters.',
        ]);

        $email    = trim($request->input('email'));
        $password = $request->input('password');
        $remember = $request->boolean('remember', false);

        $driver = Driver::firstWhere('email', $email);

        if (!$driver) {
            return response()->json([
                'errors' => ['email' => ['No driver account found with that email.']],
            ], 422);
        }

        if (!Hash::check($password, $driver->password)) {
            return response()->json([
                'errors' => ['password' => ['Incorrect password.']],
            ], 422);
        }

        // Block only Inactive (status = 5) drivers; all other statuses can log in
        if ((string) $driver->status === '2') {
            return response()->json([
                'errors' => ['email' => ['Your account has been deactivated. Please contact support.']],
            ], 422);
        }

        Auth::guard('driver')->login($driver, $remember);
        $request->session()->regenerate();

        // Auto-set status to Online (1) on login
        $driver->availability = '1';
        $driver->save();

        // Broadcast availability change to admin pages in real time
        event(new DriverUpdated(
            entity: 'driverAdminUpdated',
            action: 'updated',
            data: [
                'id'             => $driver->id,
                'name'           => $driver->name,
                'username'       => $driver->username,
                'email'          => $driver->email,
                'phone'          => $driver->phone,
                'license_no'     => $driver->license_no,
                'photo'          => $driver->photo,
                'photo_url'      => ($driver->photo && $driver->photo !== 'default.jpg')
                                    ? asset('assets/driver/img/' . $driver->photo) : null,
                'status'         => $driver->status,
                'availability'   => $driver->availability,
                'lat'            => $driver->lat !== null ? (float) $driver->lat : null,
                'lng'            => $driver->lng !== null ? (float) $driver->lng : null,
                'updated_at'     => $driver->last_seen_at,
                'total_jobs'     => 0,
                'completed_jobs' => 0,
                'added_by'       => $driver->added_by,
                'created_at'     => $driver->created_at,
                'updated_by'     => $driver->updated_by,
            ]
        ));

        return response()->json([
            'success'  => true,
            'message'  => 'Login successful! Redirecting…',
            'redirect' => route('driver.dashboard'),
        ]);
    }

    public function logout(Request $request)
    {
        $driver = Auth::guard('driver')->user();

        if ($driver) {
            // Auto-set availability to Offline (3) on logout
            $driver->availability = '3';
            $driver->setRememberToken(null);
            $driver->save();

            // Broadcast availability change to admin pages in real time
            event(new DriverUpdated(
                entity: 'driverAdminUpdated',
                action: 'updated',
                data: [
                    'id'             => $driver->id,
                    'name'           => $driver->name,
                    'username'       => $driver->username,
                    'email'          => $driver->email,
                    'phone'          => $driver->phone,
                    'license_no'     => $driver->license_no,
                    'photo'          => $driver->photo,
                    'photo_url'      => ($driver->photo && $driver->photo !== 'default.jpg')
                                        ? asset('assets/driver/img/' . $driver->photo) : null,
                    'status'         => $driver->status,
                    'availability'   => $driver->availability,
                    'lat'            => $driver->lat !== null ? (float) $driver->lat : null,
                    'lng'            => $driver->lng !== null ? (float) $driver->lng : null,
                    'updated_at'     => $driver->last_seen_at,
                    'total_jobs'     => 0,
                    'completed_jobs' => 0,
                    'added_by'       => $driver->added_by,
                    'created_at'     => $driver->created_at,
                    'updated_by'     => $driver->updated_by,
                ]
            ));
        }

        Auth::guard('driver')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
        }

        return redirect()->route('driver.login');
    }
}
