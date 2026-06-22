<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Models\Seat;
use App\Services\RemoteApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function index()
    {
        return $this->dashboard();
    }

    public function dashboard()
    {
        $api = new RemoteApi();

        $stats = null;
        $recent_bookings = null;
        $active_trips = null;
        $chart_data = null;

        try {
            $r = $api->get('/admin/dashboard/stats');
            if ($r->successful()) {
                $stats = $r->json('data') ?? $r->json();

                // Normalize older/newer API shapes: if backend returns keys like
                // `total_vehicles` / `total_bookings` map them to keys expected
                // by the view (`vehicles`, `bookings`, `schedules`, `drivers`).
                if (is_array($stats) && (isset($stats['total_vehicles']) || isset($stats['total_bookings']))) {
                    $stats = [
                        'vehicles' => $stats['total_vehicles'] ?? ($stats['vehicles'] ?? 0),
                        'schedules' => $stats['total_schedules'] ?? ($stats['schedules'] ?? 0),
                        'bookings' => $stats['total_bookings'] ?? ($stats['bookings'] ?? 0),
                        'active_trips' => $stats['active_trips'] ?? ($stats['active_trips'] ?? 0),
                        'drivers' => $stats['total_drivers'] ?? ($stats['drivers'] ?? 0),
                    ];
                }
            }
        } catch (\Exception $e) {
        }

        try {
            $r = $api->get('/admin/dashboard/bookings');
            if ($r->successful()) {
                $recent_bookings = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
        }

        try {
            $r = $api->get('/admin/dashboard/vehicles');
            if ($r->successful()) {
                // may be useful in dashboard
            }
        } catch (\Exception $e) {
        }

        // Chart data fallback
        try {
            $r = $api->get('/admin/dashboard/revenue');
            if ($r->successful()) {
                $chart_data = $r->json('data') ?? $r->json();
            }
        } catch (\Exception $e) {
        }

        // Local fallback if API didn't return data
        if (is_null($stats)) {
            $stats = [
                'vehicles' => Vehicle::count(),
                'schedules' => Schedule::count(),
                'bookings' => Booking::count(),
                'active_trips' => Trip::whereIn('status', ['boarding', 'on-going', 'delayed', 'arrived'])->count(),
                'drivers' => User::where('role', 'driver')->count(),
            ];
        }

        if (is_null($recent_bookings)) {
            $recent_bookings = Booking::with(['user', 'schedule'])->latest()->take(5)->get();
        }

        if (is_null($active_trips)) {
            $active_trips = Trip::with(['schedule.vehicle', 'schedule.driver', 'schedule.bookings.user'])->whereIn('status', ['boarding', 'on-going', 'delayed', 'arrived'])->get();
        }

        if (is_null($chart_data)) {
            $booking_stats = Booking::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $chart_data = [
                'labels' => $booking_stats->pluck('date'),
                'values' => $booking_stats->pluck('total'),
            ];
        }

        return view('admin.dashboard', compact('stats', 'recent_bookings', 'active_trips', 'chart_data'));
    }

    // Vehicle Management
    public function vehicles(Request $request)
    {
        $api = new RemoteApi();
        $vehicles = null;

        try {
            $r = $api->get('/admin/vehicles', $request->only('search'));
            if ($r->successful()) {
                $vehicles = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
        }

        if (is_null($vehicles)) {
            $query = Vehicle::query();
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('license_plate', 'like', "%{$search}%");
            }
            $vehicles = $query->get();
        }

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function createVehicle()
    {
        // Need drivers list for assignment; prefer remote
        $api = new RemoteApi();
        $drivers = null;
        try {
            $r = $api->get('/admin/drivers');
            if ($r->successful()) {
                $drivers = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
        }

        if (is_null($drivers)) {
            $drivers = User::where('role', 'driver')->get();
        }

        return view('admin.vehicles.create', compact('drivers'));
    }

    public function storeVehicle(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'license_plate' => 'required|unique:vehicles',
            'capacity' => 'required|integer',
        ]);

        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/vehicles', $request->all());
            if ($r->successful()) {
                return redirect()->route('admin.vehicles')->with('success', 'Vehicle created successfully');
            }
        } catch (\Exception $e) {
        }

        Vehicle::create($request->all());
        return redirect()->route('admin.vehicles')->with('success', 'Vehicle created successfully');
    }

    public function editVehicle(Vehicle $vehicle)
    {
        // Prefer remote vehicle detail
        $api = new RemoteApi();
        try {
            $r = $api->get('/admin/vehicles/'.$vehicle->id);
            if ($r->successful()) {
                $remote = $r->json('data') ?? $r->json();
                return view('admin.vehicles.edit', ['vehicle' => (object)$remote]);
            }
        } catch (\Exception $e) {
        }

        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function updateVehicle(Request $request, Vehicle $vehicle)
    {
        $this->validate($request, [
            'name' => 'required',
            'license_plate' => 'required|unique:vehicles,license_plate,' . $vehicle->id,
            'capacity' => 'required|integer',
        ]);

        $api = new RemoteApi();
        try {
            $r = $api->put('/admin/vehicles/'.$vehicle->id, $request->all());
            if ($r->successful()) {
                return redirect()->route('admin.vehicles')->with('success', 'Vehicle updated successfully');
            }
        } catch (\Exception $e) {
        }

        $vehicle->update($request->all());
        return redirect()->route('admin.vehicles')->with('success', 'Vehicle updated successfully');
    }

    public function deleteVehicle(Vehicle $vehicle)
    {
        $api = new RemoteApi();
        try {
            $r = $api->delete('/admin/vehicles/'.$vehicle->id);
            if ($r->successful()) {
                return redirect()->route('admin.vehicles')->with('success', 'Vehicle deleted successfully');
            }
        } catch (\Exception $e) {
        }

        $vehicle->delete();
        return redirect()->route('admin.vehicles')->with('success', 'Vehicle deleted successfully');
    }

    // Schedule Management
    public function schedules(Request $request)
    {
        $api = new RemoteApi();
        $schedules = null;
        try {
            $r = $api->get('/admin/schedules', $request->only('origin','destination'));
            if ($r->successful()) {
                $schedules = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
        }

        if (is_null($schedules)) {
            $query = Schedule::with(['vehicle', 'driver']);

            if ($request->has('origin')) {
                $query->where('origin', 'like', "%{$request->get('origin')}%");
            }
            if ($request->has('destination')) {
                $query->where('destination', 'like', "%{$request->get('destination')}%");
            }

            $schedules = $query->get();
        }

        return view('admin.schedules.index', compact('schedules'));
    }

    public function createSchedule()
    {
        $api = new RemoteApi();
        $vehicles = null; $drivers = null;
        try {
            $r = $api->get('/admin/vehicles');
            if ($r->successful()) $vehicles = collect($r->json('data') ?? $r->json());
        } catch (\Exception $e) {}
        try {
            $r = $api->get('/admin/drivers');
            if ($r->successful()) $drivers = collect($r->json('data') ?? $r->json());
        } catch (\Exception $e) {}

        if (is_null($vehicles)) $vehicles = Vehicle::all();
        if (is_null($drivers)) {
            if (Schema::hasColumn('users','role')) {
                $drivers = User::where('role','driver')->get();
            } else {
                $drivers = User::whereNotNull('driver_code')->get();
            }
        }
        return view('admin.schedules.create', compact('vehicles', 'drivers'));
    }

    public function storeSchedule(Request $request)
    {
        $this->validate($request, [
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:users,id',
            'origin' => 'required',
            'destination' => 'required',
            'departure_time' => 'required|date',
        ]);

        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/schedules', $request->all());
            if ($r->successful()) {
                return redirect()->route('admin.schedules')->with('success', 'Schedule created successfully');
            }
        } catch (\Exception $e) {}

        DB::transaction(function () use ($request) {
            $schedule = Schedule::create($request->all());

            // Create seats based on vehicle capacity
            $vehicle = Vehicle::find($request->vehicle_id);
            for ($i = 1; $i <= $vehicle->capacity; $i++) {
                Seat::create([
                    'schedule_id' => $schedule->id,
                    'seat_number' => (string)$i,
                    'status' => 'available',
                ]);
            }

            // Create initial trip record
            Trip::create([
                'schedule_id' => $schedule->id,
                'status' => 'scheduled',
            ]);
        });

        return redirect()->route('admin.schedules')->with('success', 'Schedule created successfully');
    }

    public function deleteSchedule(Schedule $schedule)
    {
        $api = new RemoteApi();
        try {
            $r = $api->delete('/admin/schedules/'.$schedule->id);
            if ($r->successful()) {
                return redirect()->route('admin.schedules')->with('success', 'Schedule deleted successfully');
            }
        } catch (\Exception $e) {}

        $schedule->delete();
        return redirect()->route('admin.schedules')->with('success', 'Schedule deleted successfully');
    }

    // User/Driver Management
    public function users(Request $request)
    {
        $api = new RemoteApi();
        $users = null;
        try {
            $r = $api->get('/admin/users', $request->only('search'));
            if ($r->successful()) {
                $users = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {}

        if (is_null($users)) {
            $query = User::query();
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            }
            $users = $query->get();
        }

        return view('admin.users.index', compact('users'));
    }

    // User CRUD (web)
    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'role' => 'required|in:customer,driver,admin',
            'phone' => 'nullable',
            'driver_code' => 'nullable|unique:users,driver_code',
            'password' => 'required|min:8',
        ]);

        $data = $request->only(['name','email','role','phone','driver_code']);
        $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);

        if ($data['role'] === 'driver' && empty($data['driver_code'])) {
            $data['driver_code'] = 'DRV'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
        }

        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/users', $data + ['password' => $request->password]);
            if ($r->successful()) {
                return redirect()->route('admin.users')->with('success','User created');
            }
        } catch (\Exception $e) {}

        \App\Models\User::create($data);
        return redirect()->route('admin.users')->with('success','User created');
    }

    public function editUser(\App\Models\User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, \App\Models\User $user)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:customer,driver,admin',
            'phone' => 'nullable',
            'driver_code' => 'nullable|unique:users,driver_code,'.$user->id,
            'password' => 'nullable|min:8',
        ]);

        $data = $request->only(['name','email','role','phone','driver_code']);
        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $api = new RemoteApi();
        try {
            $r = $api->put('/admin/users/'.$user->id, $data + ($request->filled('password') ? ['password' => $request->password] : []));
            if ($r->successful()) {
                return redirect()->route('admin.users')->with('success','User updated');
            }
        } catch (\Exception $e) {}

        $user->update($data);
        return redirect()->route('admin.users')->with('success','User updated');
    }

    public function deleteUser(\App\Models\User $user)
    {
        $api = new RemoteApi();
        try {
            $r = $api->delete('/admin/users/'.$user->id);
            if ($r->successful()) {
                return redirect()->route('admin.users')->with('success','User deleted');
            }
        } catch (\Exception $e) {}

        $user->delete();
        return redirect()->route('admin.users')->with('success','User deleted');
    }

    // Booking Monitoring
    public function bookings(Request $request)
    {
        // Try remote API first
        $api = new RemoteApi();
        try {
            $r = $api->get('/admin/bookings', $request->only('status','search'));
            if ($r->successful()) {
                $bookings = collect($r->json('data') ?? $r->json());
                return view('admin.bookings.index', compact('bookings'));
            }
        } catch (\Exception $e) {
        }

        \App\Http\Controllers\BookingController::releaseExpiredBookings();

        $query = Booking::with(['user', 'schedule', 'seat']);

        if ($request->has('status') && $request->get('status') != '') {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('search') && $request->get('search') != '') {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhereHas('schedule', function ($sq) use ($search) {
                    $sq->where('origin', 'like', "%{$search}%")
                       ->orWhere('destination', 'like', "%{$search}%");
                });
            });
        }

        $bookings = $query->latest()->get();

        // Group by payment_code to show as one transaction in admin
        $bookings = $bookings->groupBy('payment_code')->map(function ($group) {
            $first = $group->first();
            
            // Gabungkan label kursi
            $seats = $group->map(function($b) {
                $num = intval($b->seat?->seat_number);
                if (!$num) return $b->seat?->seat_number;
                $row = floor(($num - 1) / 4) + 1;
                $col = ['A', 'B', 'C', 'D'][($num - 1) % 4];
                return $num . " ($row$col)";
            })->filter()->implode(', ');

            // Ambil bukti bayar dari salah satu booking dalam grup (jika ada)
            $proof = $group->first(function($b) { return !empty($b->payment_proof); })?->payment_proof;
            $first->payment_proof = $proof;

            // Hitung total harga (Harga dasar + Kode Unik sekali saja)
            $totalBase = $group->sum(function($b) {
                return $b->total_price ?? ($b->schedule->price ?? 0);
            });
            $first->aggregated_total = $totalBase + ($first->unique_code ?? 0);
            $first->aggregated_seats = $seats;
            $first->group_count = $group->count();
            $first->related_ids = $group->pluck('id'); // Simpan buat konfirmasi massal

            return $first;
        })->values();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function verifications()
    {
        $api = new RemoteApi();
        try {
            $r = $api->get('/admin/bookings', ['status' => 'pending_payment']);
            if ($r->successful()) {
                $bookings = collect($r->json('data') ?? $r->json());
                return view('admin.bookings.verifications', compact('bookings'));
            }
        } catch (\Exception $e) {}

        // Fallback local
        $bookings = Booking::with(['user', 'schedule'])
            ->where('status', 'pending_payment')
            ->latest()
            ->get();

        return view('admin.bookings.verifications', compact('bookings'));
    }

    public function confirmBookingPayment(Booking $booking)
    {
        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/bookings/'.$booking->id.'/approve');
            if ($r->successful()) {
                return redirect()->back()->with('success', ($r->json('count') ?? 1) . ' kursi berhasil dikonfirmasi sekaligus.');
            }
        } catch (\Exception $e) {}

        // Fallback local
        $bookings = Booking::where('payment_code', $booking->payment_code)->get();
        
        foreach ($bookings as $b) {
            if ($b->status === 'pending_payment' || $b->status === 'pending_verification') {
                $b->update(['status' => 'booked']);
            }
        }

        return redirect()->back()->with('success', count($bookings) . ' kursi berhasil dikonfirmasi sekaligus.');
    }

    public function rejectBookingPayment(Booking $booking)
    {
        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/bookings/'.$booking->id.'/cancel');
            if ($r->successful()) {
                return redirect()->back()->with('success', ($r->json('count') ?? 1) . ' kursi berhasil ditolak sekaligus.');
            }
        } catch (\Exception $e) {}

        // Fallback local
        $bookings = Booking::where('payment_code', $booking->payment_code)->get();

        foreach ($bookings as $b) {
            if ($b->status === 'pending_payment' || $b->status === 'pending_verification') {
                $b->update(['status' => 'cancelled']);
                if ($b->seat) {
                    $b->seat->update(['status' => 'available']);
                }
            }
        }

        return redirect()->back()->with('success', count($bookings) . ' kursi berhasil ditolak sekaligus.');
    }

    // Trip Monitoring
    public function trips(Request $request)
    {
        $api = new RemoteApi();
        try {
            $r = $api->get('/admin/trips', $request->only('status'));
            if ($r->successful()) {
                $trips = collect($r->json('data') ?? $r->json());
                return view('admin.trips.index', compact('trips'));
            }
        } catch (\Exception $e) {}

        $query = Trip::with([
            'schedule.vehicle',
            'schedule.driver',
            'schedule.bookings.user',
            'schedule.bookings.seat',
            'locations'
        ]);

        if ($request->has('status') && $request->get('status') != '') {
            $query->where('status', $request->get('status'));
        }

        $trips = $query->latest()->get();
        return view('admin.trips.index', compact('trips'));
    }

    public function editSchedule(Schedule $schedule)
    {
        $vehicles = Vehicle::all();
        $drivers = User::where('role', 'driver')->get();
        return view('admin.schedules.edit', compact('schedule', 'vehicles', 'drivers'));
    }

    public function updateSchedule(Request $request, Schedule $schedule)
    {
        $this->validate($request, [
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:users,id',
            'origin' => 'required',
            'destination' => 'required',
            'departure_time' => 'required|date',
        ]);

        $vehicle = Vehicle::find($request->vehicle_id);
        $oldVehicleId = $schedule->vehicle_id;

        // Check if there are bookings before changing vehicle capacity/type
        if ($oldVehicleId != $vehicle->id) {
            $hasBookings = Booking::where('schedule_id', $schedule->id)->where('status', '!=', 'cancelled')->exists();
            if ($hasBookings) {
                return redirect()->back()->withErrors(['vehicle_id' => 'Cannot change vehicle because this schedule already has active bookings.']);
            }

            // Recreate seats if no active bookings exist
            Seat::where('schedule_id', $schedule->id)->delete();
            for ($i = 1; $i <= $vehicle->capacity; $i++) {
                Seat::create([
                    'schedule_id' => $schedule->id,
                    'seat_number' => (string)$i,
                    'status' => 'available',
                ]);
            }
        }

        $schedule->update($request->all());

        return redirect()->route('admin.schedules')->with('success', 'Schedule updated successfully');
    }

    public function activeTripsLocations(Request $request)
    {
        $api = new RemoteApi();
        try {
            // Fetch active trips from API
            $r = $api->get('/admin/trips', ['status' => 'boarding']);
            if ($r->successful()) {
                $trips = collect($r->json('data') ?? $r->json());
                // For each trip fetch details (includes locations)
                $detailed = $trips->map(function ($t) use ($api) {
                    $id = is_array($t) ? ($t['id'] ?? null) : ($t->id ?? null);
                    if (! $id) return null;
                    try {
                        $rr = $api->get('/admin/trips/'.$id);
                        if ($rr->successful()) return $rr->json();
                    } catch (\Exception $e) {}
                    return $t;
                })->filter();

                // Map to same structure as local fallback
                $data = collect($detailed)->map(function ($t) {
                    $schedule = $t['schedule'] ?? (is_object($t) ? $t->schedule ?? null : null);
                    $locations = $t['locations'] ?? [];
                    $passengers = [];
                    if (!empty($schedule['bookings'] ?? null)) {
                        $passengers = collect($schedule['bookings'])->map(function ($b) {
                            return [
                                'name' => $b['user']['name'] ?? ($b['user']->name ?? 'User'),
                                'seat' => $b['seat']['seat_number'] ?? ($b['seat']->seat_number ?? $b['seat_id'] ?? ''),
                                'phone' => $b['user']['phone'] ?? ($b['user']->phone ?? ''),
                            ];
                        })->toArray();
                    }

                    return [
                        'id' => $t['id'] ?? ($t->id ?? null),
                        'origin' => $schedule['origin'] ?? ($schedule->origin ?? null),
                        'destination' => $schedule['destination'] ?? ($schedule->destination ?? null),
                        'driver' => $schedule['driver']['name'] ?? ($schedule->driver->name ?? 'Driver'),
                        'vehicle' => $schedule['vehicle']['license_plate'] ?? ($schedule->vehicle->license_plate ?? ''),
                        'status' => $t['status'] ?? ($t->status ?? null),
                        'locations' => collect($locations)->map(function ($loc) {
                            return [$loc['latitude'] ?? ($loc->latitude ?? null), $loc['longitude'] ?? ($loc->longitude ?? null)];
                        })->toArray(),
                        'passengers' => $passengers,
                    ];
                })->values();

                return response()->json($data);
            }
        } catch (\Exception $e) {}

        // Local fallback
        $trips = Trip::with([
            'schedule.vehicle',
            'schedule.driver',
            'schedule.bookings' => function($q) {
                $q->where('status', '!=', 'cancelled')->with(['user', 'seat']);
            },
            'locations'
        ])->whereIn('status', ['boarding', 'on-going', 'delayed', 'arrived'])->get();

        $data = $trips->map(function ($t) {
            return [
                'id' => $t->id,
                'origin' => $t->schedule?->origin,
                'destination' => $t->schedule?->destination,
                'driver' => $t->schedule?->driver?->name ?? 'Driver',
                'vehicle' => $t->schedule?->vehicle?->license_plate ?? '',
                'status' => $t->status,
                'locations' => $t->locations->map(function ($loc) {
                    return [$loc->latitude, $loc->longitude];
                })->toArray(),
                'passengers' => $t->schedule->bookings->map(function ($booking) {
                    return [
                        'name' => $booking->user?->name ?? 'User',
                        'seat' => $booking->seat?->seat_number ?? $booking->seat_id,
                        'phone' => $booking->user?->phone ?? '',
                    ];
                })->toArray()
            ];
        });

        return response()->json($data);
    }
}
