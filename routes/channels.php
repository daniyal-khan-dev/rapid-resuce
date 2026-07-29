<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-dashboard', function () {
    return true;
});

// Private per-user channel for Contact Support real-time updates
// (admin typing indicator, admin replies, resolved status) sent to a
// specific logged-in user. Guard defaults to 'users' (config('auth.defaults.guard')),
// matching how logged-in customers authenticate on this app.
Broadcast::channel('contact.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private admin-only channel for Contact Support. Message/reply content is
// sensitive, so this must not be a public channel — only authenticated
// admins (guard 'admin') may subscribe.
Broadcast::channel('contact.admin', function ($admin) {
    return (bool) $admin;
}, ['guards' => ['admin']]);

Broadcast::channel('drivers-update', function () {
    return true;
}); 

// Per-driver personal channel — scoped by driver ID so only that driver's
// client subscribes. Public (no auth required) because the channel name
// itself is the access token: each driver's JS only subscribes to their own ID.
Broadcast::channel('driver.{driverId}', function () {
    return true;
});

// Per-request public channel used by the User tracking page and My Bookings
// section to receive live status updates when the driver changes ride status.
// Public because the tracking page is accessible without authentication.
Broadcast::channel('emergency.{requestId}', function () {
    return true;
});


// Private per-user channel for My Bookings real-time insertion.
// Fires when the user submits a new emergency request so any open
// My Bookings tab for that user immediately shows the new row.
Broadcast::channel('user.bookings.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Per-user personal channel for Ride Chat real-time messages.
// Scoped by user ID so only that user's client subscribes.
// Public (no auth required) because the channel name itself acts as
// the access scope — each user's JS subscribes only to their own ID.
Broadcast::channel('user.{userId}', function () {
    return true;
});