<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // Minimal helper used by admin views to release expired pending bookings.
    public static function releaseExpiredBookings()
    {
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
