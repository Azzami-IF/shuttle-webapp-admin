<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Seat;
use App\Services\RemoteApi;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // Minimal helper used by admin views to release expired pending bookings.
    public static function releaseExpiredBookings()
    {
        // Prefer remote API trigger; fall back to local DB operations if remote not available
        try {
            $api = new RemoteApi();
            $r = $api->post('/admin/bookings/release-expired');
            if ($r->successful()) {
                return;
            }
        } catch (\Exception $e) {
            // ignore and fallback
        }

        $expiredBookings = Booking::where('status', 'pending_payment')
            ->where('created_at', '<', now()->subMinutes(15))
            ->get();

        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'cancelled']);
                if ($booking->seat) {
                    $booking->seat->update(['status' => 'available']);
                }
            });
        }
    }
}
