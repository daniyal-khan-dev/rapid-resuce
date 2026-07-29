<?php

namespace App\Http\Controllers\User;

use App\Events\UserUpdated as UserUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Mail\EmailChangeInitiatedMail;
use App\Mail\EmailChangedNewMail;
use App\Mail\EmailChangedOldMail;
use App\Mail\EmailChangeVerificationMail;
use App\Models\User\EmailVerificationCode;
use App\Models\User\MedicalCard;
use App\Models\User\PasswordResetCode;
use App\Models\User\User;
use App\Models\User\UserDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserProfileController extends Controller
{

    public function update(Request $request)
    {
        $currentUser = Auth::guard('users')->user();
        $user = User::findOrFail($currentUser->id);
        $userDetail = UserDetail::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'edit_username'  => 'required|string|max:30|unique:users,username,' . $user->id,
            'edit_first_name'=> 'required|string|max:15',
            'edit_last_name' => 'required|string|max:15',
            'edit_phone'     => 'required|string|max:11|unique:user_details,phone,' . $userDetail->id,
            'edit_dob'       => 'required|date',
            'edit_address'   => 'required|string|max:100',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ], [
            'edit_username.unique'    => 'This username is already taken.',
            'edit_phone.unique'       => 'This phone number is already in use.',
            'edit_username.max'       => 'Username cannot exceed 30 characters.',
            'edit_first_name.max'     => 'First name cannot exceed 15 characters.',
            'edit_last_name.max'      => 'Last name cannot exceed 15 characters.',
            'edit_phone.max'          => 'Phone number cannot exceed 11 digits.',
            'edit_address.max'        => 'Address cannot exceed 100 characters.',
            'edit_dob.date'           => 'Please enter a valid date of birth.',
            'image.image'             => 'Uploaded file must be an image.',
            'image.mimes'             => 'Image must be JPG, JPEG, PNG, WEBP, or GIF.',
            'image.max'               => 'Image size must not exceed 2 MB.',
        ]);

        DB::beginTransaction();

        try {
            $pfpUrl = null;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
            
                // OLD IMAGE DELETE (FIXED)
                if ($userDetail->profile_picture) {
                    $oldPath = public_path('assets/user/img/users/' . $userDetail->profile_picture);
            
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
            
                // NEW IMAGE UPLOAD
                $file->move(public_path('assets/user/img/users'), $fileName);
                $userDetail->profile_picture = $fileName;
                $pfpUrl = asset('assets/user/img/users/' . $fileName);
            }

            $userDetail->update([
                'first_name'    => $request->edit_first_name,
                'last_name'     => $request->edit_last_name,
                'phone'         => $request->edit_phone,
                'date_of_birth' => $request->edit_dob,
                'address'       => $request->edit_address,
            ]);

            $user->username = $request->edit_username;
            $user->save();

            DB::commit();

            event(new UserUpdatedEvent(
                id:              $user->id,
                username:        $user->username,
                status:          (string) $user->status,
                name:            $request->edit_first_name . ' ' . $request->edit_last_name,
                email:           $userDetail->email,
                phone:           $userDetail->phone,
                profile_picture: $userDetail->profile_picture ?? 'default.jpg',
                created_at:      $userDetail->created_at?->format('d M Y'),
                verified:        (bool) $userDetail->email_verified_at,
                action:          'updated',
            ));

            return response()->json([
                'success'  => true,
                'message'  => 'Profile updated successfully.',
                'pfp'      => $pfpUrl,
                'userdata' => $user,
                'data'     => $userDetail,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public function sendEmailChangeCode(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('users')->user();
        $userDetail  = UserDetail::where('user_id', $currentUser->id)->firstOrFail();

        $request->validate([
            'new_email' => 'required|email|max:100|unique:user_details,email,' . $userDetail->id,
        ], [
            'new_email.unique' => 'This email is already in use by another account.',
        ]);

        $newEmail = $request->input('new_email');

        if ($newEmail === $userDetail->email) {
            return response()->json(['success' => false, 'message' => 'This is already your current email address.'], 400);
        }

        $today = now()->toDateString();
        $existing = EmailVerificationCode::where('email', $newEmail)->first();
        $resendCount = 0;
        if ($existing && $existing->resend_date && $existing->resend_date->toDateString() === $today) {
            $resendCount = $existing->resend_count;
        }
        if ($resendCount >= 4) {
            return response()->json(['success' => false, 'limit_reached' => true, 'message' => 'Daily send limit reached. Try again tomorrow.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::updateOrCreate(
            ['email' => $newEmail],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => $resendCount,
                'resend_date'  => $today,
            ]
        );

        try {
            Mail::to($newEmail)->send(new EmailChangeVerificationMail($code, $userDetail->first_name));
            Mail::to($userDetail->email)->send(new EmailChangeInitiatedMail($userDetail->first_name, $newEmail));    
        } catch (\Exception $e) {
            Log::error('Email change code mail failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send code. Please try again.'], 500);
        }

        $request->session()->put('pending_email_change', $newEmail);

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your new email address.',
        ]);
    }

    public function resendEmailChangeCode(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('users')->user();
        $userDetail  = UserDetail::where('user_id', $currentUser->id)->firstOrFail();
        $newEmail    = $request->session()->get('pending_email_change');

        if (!$newEmail) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please start again.'], 400);
        }

        $today   = now()->toDateString();
        $record  = EmailVerificationCode::where('email', $newEmail)->first();
        $resendCount = 0;
        if ($record && $record->resend_date && $record->resend_date->toDateString() === $today) {
            $resendCount = $record->resend_count;
        }
        if ($resendCount >= 4) {
            return response()->json(['success' => false, 'limit_reached' => true, 'message' => 'Maximum 4 resends per day reached.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::updateOrCreate(
            ['email' => $newEmail],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => $resendCount + 1,
                'resend_date'  => $today,
            ]
        );

        try {
            Mail::to($newEmail)->send(new EmailChangeVerificationMail($code, $userDetail->first_name));
            Mail::to($userDetail->email)->send(new EmailChangeInitiatedMail($userDetail->first_name, $newEmail));
        } catch (\Exception $e) {
            Log::error('Email change resend failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to resend code.'], 500);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Code resent.',
            'resend_count' => $resendCount + 1,
        ]);
    }

    public function verifyEmailChange(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $currentUser = Auth::guard('users')->user();
        $userDetail  = UserDetail::where('user_id', $currentUser->id)->firstOrFail();
        $newEmail    = $request->session()->get('pending_email_change');

        if (!$newEmail) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please start again.'], 400);
        }

        $record = EmailVerificationCode::where('email', $newEmail)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No code found. Please request a new one.'], 404);
        }
        if (now()->isAfter($record->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Code has expired. Please request a new one.'], 400);
        }
        if ($record->code !== $request->input('code')) {
            return response()->json(['success' => false, 'message' => 'Incorrect code. Please check and try again.'], 400);
        }

        $oldEmail = $userDetail->email;
        $userDetail->email            = $newEmail;
        $userDetail->email_verified_at = now();
        $userDetail->save();

        $record->delete();
        EmailVerificationCode::where('email', $oldEmail)->delete();
        $request->session()->forget('pending_email_change');

        $changedUser = User::find($currentUser->id);
        if ($changedUser) {
            event(new UserUpdatedEvent(
                id:              $changedUser->id,
                username:        $changedUser->username,
                status:          (string) $changedUser->status,
                name:            $userDetail->first_name . ' ' . $userDetail->last_name,
                email:           $newEmail,
                phone:           $userDetail->phone,
                profile_picture: $userDetail->profile_picture ?? 'default.jpg',
                created_at:      $userDetail->created_at?->format('d M Y'),
                verified:        true,
                action:          'updated',
            ));
        }
        
        try {
            Mail::to($newEmail)->send(new EmailChangedNewMail($userDetail->first_name));
        } catch (\Exception $e) {
            Log::error('Email change confirmation mail to new address failed: ' . $e->getMessage());
        }

        try {
            Mail::to($oldEmail)->send(new EmailChangedOldMail($userDetail->first_name, $newEmail));
        } catch (\Exception $e) {
            Log::error('Email change confirmation mail to old address failed: ' . $e->getMessage());
        }
        
        return response()->json([
            'success'   => true,
            'message'   => 'Email updated and verified successfully.',
            'new_email' => $newEmail,
        ]);
    }

    public function sendPasswordChangeCode(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('users')->user();
        $userDetail  = UserDetail::where('user_id', $currentUser->id)->firstOrFail();

        $request->validate([
            'new_password'     => 'required|min:7',
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.min'         => 'Password must be at least 7 characters.',
            'confirm_password.same'    => 'Passwords do not match.',
        ]);

        $email = $userDetail->email;
        $today = now()->toDateString();
        $existing = PasswordResetCode::where('email', $email)->first();
        $resendCount = 0;
        if ($existing && $existing->resend_date && $existing->resend_date->toDateString() === $today) {
            $resendCount = $existing->resend_count;
        }
        if ($resendCount >= 4) {
            return response()->json(['success' => false, 'limit_reached' => true, 'message' => 'Daily send limit reached. Try again tomorrow.'], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetCode::updateOrCreate(
            ['email' => $email],
            [
                'code'         => $code,
                'expires_at'   => now()->addMinute(),
                'resend_count' => $resendCount,
                'resend_date'  => $today,
            ]
        );

        try {
            Mail::to($email)->send(new PasswordResetCodeMail($code, $userDetail->first_name));
        } catch (\Exception $e) {
            Log::error('Password change code mail failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send code. Please try again.'], 500);
        }

        $request->session()->put('pending_password_change', $request->input('new_password'));

        return response()->json([
            'success'      => true,
            'message'      => 'A verification code has been sent to your registered email.',
            'masked_email' => $this->maskEmail($email),
        ]);
    }

    public function resendPasswordChangeCode(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('users')->user();
        $userDetail  = UserDetail::where('user_id', $currentUser->id)->firstOrFail();
        $email       = $userDetail->email;

        $today  = now()->toDateString();
        $record = PasswordResetCode::where('email', $email)->first();
        $resendCount = 0;
        if ($record && $record->resend_date && $record->resend_date->toDateString() === $today) {
            $resendCount = $record->resend_count;
        }
        if ($resendCount >= 4) {
            return response()->json(['success' => false, 'limit_reached' => true, 'message' => 'Maximum 4 resends per day reached.'], 429);
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
            Log::error('Password change resend failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to resend code.'], 500);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Code resent.',
            'resend_count' => $resendCount + 1,
        ]);
    }

    public function verifyAndChangePassword(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $currentUser = Auth::guard('users')->user();
        $userDetail  = UserDetail::where('user_id', $currentUser->id)->firstOrFail();
        $user        = User::findOrFail($currentUser->id);
        $email       = $userDetail->email;
        $newPassword = $request->session()->get('pending_password_change');

        if (!$newPassword) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please start again.'], 400);
        }

        $record = PasswordResetCode::where('email', $email)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No code found. Please request a new one.'], 404);
        }
        if (now()->isAfter($record->expires_at)) {
            return response()->json(['success' => false, 'message' => 'Code has expired. Please request a new one.'], 400);
        }
        if ($record->code !== $request->input('code')) {
            return response()->json(['success' => false, 'message' => 'Incorrect code. Please check and try again.'], 400);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $record->delete();
        $request->session()->forget('pending_password_change');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    public function storeMedicalCard(Request $request): JsonResponse
    {
        $user = Auth::guard('users')->user();

        $request->validate([
            'blood_type'      => 'nullable|string|max:5',
            'medical_history' => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z\s,]+$/'],
            'allergies'       => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z\s,]+$/'],
            'medications'     => ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z\s,]+$/'],
            'contact_name'    => 'nullable|string|max:50',
            'relation'        => 'nullable|string|max:20',
            'contact_phone'   => 'nullable|string|max:11',
        ], [
            'medical_history.regex' => 'Medical history may only contain letters, spaces, and commas.',
            'allergies.regex'       => 'Allergies may only contain letters, spaces, and commas.',
            'medications.regex'     => 'Medications may only contain letters, spaces, and commas.',
        ]);

        DB::beginTransaction();

        try {
            $card = MedicalCard::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'blood_type'      => $request->blood_type,
                    'medical_history' => $request->medical_history,
                    'allergies'       => $request->allergies,
                    'medications'     => $request->medications,
                    'contact_name'    => $request->contact_name,
                    'relation'        => $request->relation,
                    'contact_phone'   => $request->contact_phone,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Medical card saved successfully.',
                'card'    => $card,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save medical card: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteMedicalCard(Request $request): JsonResponse
    {
        $user = Auth::guard('users')->user();

        DB::beginTransaction();
        try {
            MedicalCard::where('user_id', $user->id)->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Medical card deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete medical card: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function maskEmail(string $email): string
    {
        $at = strrpos($email, '@');
        if ($at === false || $at <= 3) return $email;
        return substr($email, 0, 3) . str_repeat('*', $at - 3) . substr($email, $at);
    }
}
