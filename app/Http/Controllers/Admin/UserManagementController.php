<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['details', 'medicalCard'])->latest()->get();
        return view('admin.pages.users', compact('users'));
    }

    public function view($id)
    {
        $user = User::with(['details', 'medicalCard', 'contactMessages'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'user'    => [
                'id'         => $user->id,
                'username'   => $user->username,
                'status'     => $user->status,
                'created_at' => $user->created_at?->format('d M Y, h:i A'),
                'details'    => $user->details ? [
                    'first_name'        => $user->details->first_name,
                    'last_name'         => $user->details->last_name,
                    'consumer_no'       => $user->details->consumer_no,
                    'email'             => $user->details->email,
                    'phone'             => $user->details->phone,
                    'address'           => $user->details->address,
                    'date_of_birth'     => $user->details->date_of_birth?->format('d M Y'),
                    'profile_picture'   => $user->details->profile_picture,
                    'email_verified_at' => $user->details->email_verified_at?->format('d M Y'),
                ] : null,
                'medical_card' => $user->medicalCard ? [
                    'blood_type'      => $user->medicalCard->blood_type,
                    'medical_history' => $user->medicalCard->medical_history,
                    'allergies'       => $user->medicalCard->allergies,
                    'medications'     => $user->medicalCard->medications,
                    'contact_name'    => $user->medicalCard->contact_name,
                    'relation'        => $user->medicalCard->relation,
                    'contact_phone'   => $user->medicalCard->contact_phone,
                ] : null,
                'total_messages' => $user->contactMessages->count(),
            ],
        ]);
    }
}
