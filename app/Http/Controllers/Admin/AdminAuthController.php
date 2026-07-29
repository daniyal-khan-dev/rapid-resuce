<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    private function noCache($response)
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache')->header('Expires', '0');
    }

    public function login()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        if (Auth::guard('users')->check()) {
            return redirect()->route('home');
        }
        return $this->noCache(response()->view('admin.auth.login'));
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

        $admin = Admin::firstWhere('email', $email);

        if (!$admin) {
            return response()->json([
                'errors' => ['email' => ['No admin account found with that email.']],
            ], 422);
        }

        if (!Hash::check($password, $admin->password)) {
            return response()->json([
                'errors' => ['password' => ['Incorrect password.']],
            ], 422);
        }

        if ($admin->status != 1) {
            return response()->json([
                'errors' => ['email' => ['Your account is inactive. Please contact support.']],
            ], 422);
        }

        Auth::guard('admin')->login($admin, $remember);
        $request->session()->regenerate();

        $loggedAdmin = Auth::guard('admin')->user();
        logHistory($loggedAdmin->username, $request->ip(), 'Logged in');

        return response()->json([
            'success'  => true,
            'message'  => 'Login successful! Redirecting…',
            'redirect' => route('admin.dashboard'),
        ]);
    }

    public function logout(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $admin->setRememberToken(null);
            $admin->save();
            logHistory($admin->username, $request->ip(), 'Logged out.');
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
        }

        return redirect()->route('admin.login')->with('logout_success', 'You have been logged out successfully.');
    }
}
