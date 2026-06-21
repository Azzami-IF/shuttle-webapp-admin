<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheManager
{
    const CACHE_DURATION_SHORT = 300; // 5 minutes
    const CACHE_DURATION_MEDIUM = 3600; // 1 hour
    const CACHE_DURATION_LONG = 86400; // 24 hours

    public static function getSchedules()
    {
        return Cache::remember('schedules:all', self::CACHE_DURATION_SHORT, function () {
            return \App\Models\Schedule::with(['vehicle', 'driver'])
                ->select('id', 'vehicle_id', 'driver_id', 'origin', 'destination', 'departure_time', 'estimated_duration', 'price', 'created_at')
                ->get();
        });
    }

    public static function getVehicles()
    {
        return Cache::remember('vehicles:all', self::CACHE_DURATION_MEDIUM, function () {
            return \App\Models\Vehicle::select('id', 'name', 'license_plate', 'capacity', 'created_at')->get();
        });
    }

    public static function getDashboardStats()
    {
        return Cache::remember('dashboard:stats', self::CACHE_DURATION_SHORT, function () {
            return [
                'total_vehicles' => \App\Models\Vehicle::count(),
                'total_schedules' => \App\Models\Schedule::count(),
                'total_bookings' => \App\Models\Booking::count(),
                'total_users' => \App\Models\User::where('role', 'customer')->count(),
                'total_drivers' => \App\Models\User::where('role', 'driver')->count(),
                'active_trips' => \App\Models\Trip::where('status', 'on-going')->count(),
                'pending_bookings' => \App\Models\Booking::where('status', 'pending_payment')->count(),
                'completed_trips' => \App\Models\Trip::where('status', 'completed')->count(),
            ];
        });
    }

    public static function clearCache($pattern = null)
    {
        if ($pattern) {
            Cache::forget($pattern);
        } else {
            Cache::flush();
        }
    }
}
