<?php

namespace App\Http\Controllers\User;

use App\Events\UserUpdated as UserUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Mail\VerificationCodeMail;
use App\Models\User\EmailVerificationCode;
use App\Models\User\PasswordResetCode;
use App\Models\User\User;
use App\Models\User\UserDetail;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserAuthController extends Controller
{
    private function noCache($response)
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache')->header('Expires', '0');
    }

    public function login()
    {
        if (Auth::guard('users')->check()) {
            return redirect()->route('home');
        }
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return $this->noCache(response()->view('user.auth.login'));
    }

    public function signup()
    {
        if (Auth::guard('users')->check()) {
            return redirect()->route('home');
        }
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return $this->noCache(response()->view('user.auth.signup'));
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $field = $request->input('field');
        $value = trim($request->input('value', ''));

        if (empty($value) || ! in_array($field, ['username', 'email', 'phone'])) {
            return response()->json(['available' => null]);
        }

        if ($field === 'username') {
            $taken = User::query()->where('username', $value)->exists();
            return response()->json([
                'available' => !$taken,
                'message'   => $taken ? 'This username is already taken.' : "@{$value} is available!",
            ]);
        }

        $taken = UserDetail::query()->where($field, $value)->exists();
        return response()->json([
            'available' => !$taken,
            'message'   => $taken ? ucfirst($field) . ' is already registered with another account.' : '',
        ]);
    }

    private function generateConsumerNo(): string
    {
        $lastDetail = UserDetail::orderBy('id', 'desc')->first();

        if ($lastDetail && preg_match('/C-(\d+)/', $lastDetail->consumer_no, $matches)) {
            $lastNumber = (int) $matches[1];
        } else {
            $lastNumber = 10000;
        }

        return 'C-'.($lastNumber + 1);
    }

    private function guardRecaptcha(Request $request): ?JsonResponse
    {
        $token = $request->input('g-recaptcha-response', '');
        if (! app(RecaptchaService::class)->verify($token)) {
            return response()->json([
                'errors' => ['recaptcha' => ['Please complete the human verification (reCAPTCHA).']],
            ], 422);
        }
        return null;
    }

    public function register(Request $request): JsonResponse
    {
        if ($guard = $this->guardRecaptcha($request)) {
            return $guard;
        }

        $validated = $request->validate([
            'first_name' => 'required|regex:/^[A-Za-z\s]+$/',
            'last_name'  => 'required|regex:/^[A-Za-z\s]+$/',
            'username'   => 'required|unique:users,username',
            'email'      => 'required|email|unique:user_details,email',
            'phone'      => 'nullable|regex:/^03[0-9]{9}$/',
            'password'   => 'required|min:7',
            'pfp'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'first_name.required' => 'First name is required.',
            'first_name.regex'    => 'First name can only contain letters and spaces.',
            'last_name.required'  => 'Last name is required.',
            'last_name.regex'     => 'Last name can only contain letters and spaces.',
            'username.required'   => 'Username is required.',
            'username.unique'     => 'This username is already taken. Please choose another one.',
            'email.required'      => 'Email is required.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already registered.',
            'phone.regex'         => 'Phone number must be a valid Pakistani number (03XXXXXXXXX).',
            'password.required'   => 'Password is required.',
            'password.min'        => 'Password must be at least 7 characters long.',
            'pfp.image'           => 'Profile picture must be an image.',
            'pfp.mimes'           => 'Profile picture must be jpg, jpeg, png, or webp format.',
            'pfp.max'             => 'Profile picture size must not exceed 2MB.',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'status'   => 1,
            ]);

            $fileName = null;

            if ($request->hasFile('pfp')) {
                $file     = $request->file('pfp');
                $fileName = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('assets/user/img/users'), $fileName);
            }

            UserDetail::create([
                'user_id'           => $user->id,
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'email'             => $validated['email'],
                'phone'             => $validated['phone'],
                'consumer_no'       => $this->generateConsumerNo(),
                'profile_picture'   => $fileName ?? 'default.jpg',
                'email_verified_at' => null,
            ]);

            DB::commit();

            event(new UserUpdatedEvent(
                id:              $user->id,
                username:        $user->username,
                status:          (string) $user->status,
                name:            $validated['first_name'] . ' ' . $validated['last_name'],
                email:           $validated['email'],
                phone:           $validated['phone'] ?? null,
                profile_picture: $fileName ?? 'default.jpg',
                created_at:      $user->created_at?->format('d M Y'),
                verified:        false,
                action:          'created',
            ));
                
            return response()->json([
                'success' => true,
                'message' => 'Account created! Please verify your email.',
                'email'   => $validated['email'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendVerificationCode(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $userDetail = UserDetail::where('email', $email)->first();
        if (!$userDetail) {
            return response()->json(['success' => false, 'message' => 'Email not found.'], 404);
        }

        if ($userDetail->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email is already verified.'], 400);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::updateOrCreate(
            ['email' => $email],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => 0,
                'resend_date'  => now()->toDateString(),
            ]
        );

        try {
            Mail::to($email)->send(new VerificationCodeMail($code, $userDetail->first_name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mail send failed (send): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try the Resend button or contact support.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Verification code sent to your email.']);
    }

    public function resendVerificationCode(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $userDetail = UserDetail::where('email', $email)->first();
        if (!$userDetail) {
            return response()->json(['success' => false, 'message' => 'Email not found.'], 404);
        }

        if ($userDetail->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email is already verified.'], 400);
        }

        $record = EmailVerificationCode::where('email', $email)->first();
        $today  = now()->toDateString();

        $resendCount = 0;
        if ($record && $record->resend_date && $record->resend_date->toDateString() === $today) {
            $resendCount = $record->resend_count;
        }

        if ($resendCount >= 4) {
            return response()->json([
                'success'       => false,
                'limit_reached' => true,
                'message'       => 'You have reached the maximum of 4 resend requests for today. Please try again tomorrow.',
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::updateOrCreate(
            ['email' => $email],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => $resendCount + 1,
                'resend_date'  => $today,
            ]
        );

        try {
            Mail::to($email)->send(new VerificationCodeMail($code, $userDetail->first_name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mail send failed (resend): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.',
            ], 500);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'A new verification code has been sent.',
            'resend_count' => $resendCount + 1,
        ]);
    }

    public function verifyEmailCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $email = $request->input('email');
        $code  = $request->input('code');

        $record = EmailVerificationCode::where('email', $email)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No verification code found. Please request a new one.'], 404);
        }

        if (now()->isAfter($record->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code has expired. Please request a new one.'], 400);
        }

        if ($record->code !== $code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code. Please check and try again.'], 400);
        }

        $userDetail = UserDetail::where('email', $email)->first();
        if ($userDetail) {
            $userDetail->email_verified_at = now();
            $userDetail->save();

            $verifiedUser = User::find($userDetail->user_id);
            if ($verifiedUser) {
                event(new UserUpdatedEvent(
                    id:              $verifiedUser->id,
                    username:        $verifiedUser->username,
                    status:          (string) $verifiedUser->status,
                    name:            $userDetail->first_name . ' ' . $userDetail->last_name,
                    email:           $userDetail->email,
                    phone:           $userDetail->phone,
                    profile_picture: $userDetail->profile_picture ?? 'default.jpg',
                    created_at:      $userDetail->created_at?->format('d M Y'),
                    verified:        true,
                    action:          'updated',
                ));
            }
        }

        $record->delete();

        return response()->json([
            'success'  => true,
            'message'  => 'Email verified successfully! Redirecting to login…',
            'redirect' => route('login'),
        ]);
    }

    public function sendPasswordResetCode(Request $request): JsonResponse
    {
        if ($guard = $this->guardRecaptcha($request)) {
            return $guard;
        }

        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $userDetail = UserDetail::where('email', $email)->first();
        if (!$userDetail) {
            return response()->json(['success' => false, 'message' => 'No account found with that email address.'], 404);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetCode::updateOrCreate(
            ['email' => $email],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => 0,
                'resend_date'  => now()->toDateString(),
            ]
        );

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code, $userDetail->first_name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Password reset mail failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send reset code. Please try again.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Reset code sent to your email.']);
    }

    public function resendPasswordResetCode(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $userDetail = UserDetail::where('email', $email)->first();
        if (!$userDetail) {
            return response()->json(['success' => false, 'message' => 'No account found with that email address.'], 404);
        }

        $record = PasswordResetCode::where('email', $email)->first();
        $today  = now()->toDateString();

        $resendCount = 0;
        if ($record && $record->resend_date && $record->resend_date->toDateString() === $today) {
            $resendCount = $record->resend_count;
        }

        if ($resendCount >= 4) {
            return response()->json([
                'success'       => false,
                'limit_reached' => true,
                'message'       => 'You have reached the maximum of 4 resend requests for today. Please try again tomorrow.',
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetCode::updateOrCreate(
            ['email' => $email],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => $resendCount + 1,
                'resend_date'  => $today,
            ]
        );

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code, $userDetail->first_name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Password reset resend mail failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to resend code. Please try again.'], 500);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'A new reset code has been sent.',
            'resend_count' => $resendCount + 1,
        ]);
    }

    public function verifyPasswordResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $email = $request->input('email');
        $code  = $request->input('code');

        $record = PasswordResetCode::where('email', $email)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No reset code found. Please request a new one.'], 404);
        }

        if (now()->isAfter($record->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Reset code has expired. Please request a new one.'], 400);
        }

        if ($record->code !== $code) {
            return response()->json(['success' => false, 'message' => 'Invalid reset code. Please check and try again.'], 400);
        }

        $request->session()->put('password_reset_verified_email', $email);

        return response()->json(['success' => true, 'message' => 'Code verified. Please set your new password.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'new_password'     => 'required|min:7',
            'confirm_password' => 'required|same:new_password',
        ]);

        $email = $request->session()->get('password_reset_verified_email');

        if (!$email) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please start the reset process again.'], 400);
        }

        $userDetail = UserDetail::where('email', $email)->first();
        if (!$userDetail) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        }

        $user = User::find($userDetail->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 404);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        PasswordResetCode::where('email', $email)->delete();
        $request->session()->forget('password_reset_verified_email');

        return response()->json([
            'success'  => true,
            'message'  => 'Password reset successfully! Redirecting to login…',
            'redirect' => route('login'),
        ]);
    }

    public function loginSubmit(Request $request): JsonResponse
    {
        if ($guard = $this->guardRecaptcha($request)) {
            return $guard;
        }

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string|min:7',
        ], [
            'username.required' => 'Username is required.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 7 characters.',
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');
        $remember = $request->boolean('remember', false);

        $user = User::firstWhere('username', $username);

        if (!$user) {
            return response()->json([
                'errors' => ['username' => ['No account found with that username.']],
            ], 422);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'errors' => ['password' => ['Incorrect password.']],
            ], 422);
        }

        if ($user->status != 1) {
            return response()->json([
                'errors' => ['username' => ['Your account is inactive. Please contact support.']],
            ], 422);
        }

        Auth::guard('users')->login($user, $remember);
        $request->session()->regenerate();

        return response()->json([
            'success'  => true,
            'message'  => 'Login successful! Redirecting…',
            'redirect' => route('home'),
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('users')->user();

        if ($user) {
            $user->setRememberToken(null);
            $user->save();
        }

        Auth::guard('users')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'You have been logged out successfully.']);
        }

        return redirect()->route('home');
    }
}
