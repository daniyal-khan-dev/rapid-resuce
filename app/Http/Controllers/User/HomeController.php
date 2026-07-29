<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Events\Emergency;
use App\Models\Admin\Service;
use App\Models\Admin\Ambulance;
use App\Models\Admin\Testimonial;
use App\Models\Admin\Faq;
use App\Models\Admin\Branch;
use App\Models\User\ContactMessage;
use App\Models\User\EmergencyRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private function noCache($response)
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache')->header('Expires', '0');
    }
    
    public function index()
    {
        $services = Service::whereStatus(1)->get();
        $fleetAmbs    = Ambulance::whereNotNull('card_title')->orderBy('type')->get();
        $testimonials = Testimonial::whereStatus(1)->get();
        $faqs = Faq::whereStatus(1)->get();
        $branches     = Branch::orderBy('id','ASC')->whereStatus(1)->get();
        $contactInfo = Branch::orderBy('id', 'asc')->whereStatus(1)->first();
        return $this->noCache(response()->view('user.pages.index', compact('services','testimonials','faqs','fleetAmbs','branches', 'contactInfo')));
    }

    public function firstAid()
    { 
        $contactInfo = Branch::orderBy('id', 'asc')->whereStatus(1)->first();
        return $this->noCache(response()->view('user.pages.first_aid', compact('contactInfo')));
    }

    public function tracking(Request $request, $id)
    {
        $req = EmergencyRequest::with(['ambulance', 'driver'])->findOrFail($id);
        $alreadyRated = \App\Models\User\Feedback::where('request_id', $id)->exists();
        return $this->noCache(response()->view('user.pages.tracking', compact('req', 'alreadyRated')));
    }

    public function terms()
    { 
        $contactInfo = Branch::orderBy('id', 'asc')->whereStatus(1)->first();
        return $this->noCache(response()->view('user.pages.terms', compact('contactInfo')));
    }
    
    public function privacy()
    {
        $contactInfo = Branch::orderBy('id', 'asc')->whereStatus(1)->first();
        return $this->noCache(response()->view('user.pages.privacy', compact('contactInfo')));
    }

    public function profile()
    {
        $user = Auth::guard('users')->user();
        $userDetail = $user->details;
        $medicalCard = $user->medicalCard;
        $contactMessages = ContactMessage::where('user_id', $user->id)->latest()->get();
        $myBookings = EmergencyRequest::with(['ambulance'])->where('user_id', $user->id)->latest()->get();

        if (!$userDetail) {
            $userDetail = $user->details()->create([
                'first_name' => $user->username,
                'last_name'  => '',
                'email'      => '',
                'phone'      => '',
            ]);
        }

        return $this->noCache(
            response()->view('user.pages.profile', compact('user', 'userDetail', 'medicalCard', 'contactMessages', 'myBookings'))
        );
    }

    public function cancelRide(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $user = Auth::guard('users')->user();
        if ($user) {
            // Logged-in user: ensure the ride belongs to them
            $req = EmergencyRequest::where('user_id', $user->id)->findOrFail($id);
        } else {
            // Guest: find by request ID only
            $req = EmergencyRequest::findOrFail($id);
        }
        

        if ($req->status !== '1') {
            return response()->json(['success' => false, 'message' => 'Only pending rides can be cancelled.'], 422);
        }

        $req->status       = '7';
        $req->completed_at = now();
        $req->save();

        /* Notify the tracking page and My Bookings list */
        event(new Emergency('emergency.' . $req->id, 'emergency-request-status-changed', [
            'id'     => $req->id,
            'status' => '7',
            'action' => 'cancel',
        ]));

        /* Notify admin: removes from active grid; past-rides page can insert the new row */
        event(new Emergency('admin-dashboard', 'emergency-request-status-changed', [
            'id'            => $req->id,
            'status'        => '7',
            'action'        => 'cancel',
            'rreb_id'       => $req->rreb_id,
            'type'          => $req->type,
            'hospital_name' => $req->hospital_name,
            'pickup_address'=> $req->pickup_address,
            'user_name'     => $req->user?->details?->first_name ?? 'Guest',
            'mobile_no'     => $req->mobile_no,
            'driver_name'   => null,
            'ambulance_no'  => null,
            'completed_at'  => now()->format('d M Y'),
        ]));

        return response()->json(['success' => true, 'message' => 'Ride cancelled successfully.']);
    }
}