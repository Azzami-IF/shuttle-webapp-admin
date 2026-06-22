<?php

namespace App\Http\Controllers;

use App\Models\RouteTemplate;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Services\RemoteApi;

class RouteTemplateController extends Controller
{
    public function index()
    {
        $api = new RemoteApi();

        // Try remote API first (admin endpoints), fall back to local DB if unavailable
        $templates = null;
        $vehicles = null;
        $drivers = null;

        try {
            $r = $api->get('/admin/route-templates');
            if ($r->successful()) {
                $templates = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
            // ignore, fallback to local
        }

        try {
            $r = $api->get('/admin/vehicles');
            if ($r->successful()) {
                $vehicles = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
        }

        try {
            $r = $api->get('/admin/drivers');
            if ($r->successful()) {
                $drivers = collect($r->json('data') ?? $r->json());
            }
        } catch (\Exception $e) {
        }

        // Local fallback if API didn't return data
        if (is_null($templates)) {
            $templates = RouteTemplate::with(['vehicle', 'driver'])->orderBy('origin')->get();
        }
        if (is_null($vehicles)) {
            $vehicles = Vehicle::all();
        }
        // Some hosts may have a users table without a `role` column (legacy).
        if (is_null($drivers)) {
            if (Schema::hasColumn('users', 'role')) {
                $drivers = User::where('role', 'driver')->get();
            } else {
                $drivers = User::whereNotNull('driver_code')->get();
            }
        }
        return view('admin.route-templates.index', compact('templates', 'vehicles', 'drivers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'          => 'required|exists:vehicles,id',
            'driver_id'           => 'required|exists:users,id',
            'origin'              => 'required|string',
            'destination'         => 'required|string',
            'departure_time'      => 'required',
            'price'               => 'required|numeric|min:0',
            'active_days'         => 'required|array',
            'generate_days_ahead' => 'required|integer|min:1|max:90',
        ]);

        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/route-templates', $request->all());
            if ($r->successful()) {
                return redirect()->route('admin.route-templates.index')
                    ->with('success', 'Template rute berhasil ditambahkan dan jadwal otomatis di-generate!');
            }
        } catch (\Exception $e) {
        }

        // Fallback to local
        RouteTemplate::create([
            'vehicle_id'          => $request->vehicle_id,
            'driver_id'           => $request->driver_id,
            'origin'              => $request->origin,
            'destination'         => $request->destination,
            'departure_time'      => $request->departure_time,
            'price'               => $request->price,
            'active_days'         => $request->active_days,
            'generate_days_ahead' => $request->generate_days_ahead,
            'is_active'           => true,
        ]);

        Artisan::call('schedules:generate', ['--days' => $request->generate_days_ahead]);

        return redirect()->route('admin.route-templates.index')
            ->with('success', 'Template rute berhasil ditambahkan dan jadwal otomatis di-generate!');
    }

    public function update(Request $request, RouteTemplate $routeTemplate)
    {
        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/route-templates/'.$routeTemplate->id.'/toggle');
            if ($r->successful()) {
                return back()->with('success', 'Status template diperbarui.');
            }
        } catch (\Exception $e) {
        }

        // Fallback local toggle
        $routeTemplate->update([
            'is_active' => !$routeTemplate->is_active,
        ]);
        return back()->with('success', 'Status template diperbarui.');
    }

    public function destroy(RouteTemplate $routeTemplate)
    {
        $api = new RemoteApi();
        try {
            $r = $api->delete('/admin/route-templates/'.$routeTemplate->id);
            if ($r->successful()) {
                return back()->with('success', 'Template rute dihapus.');
            }
        } catch (\Exception $e) {
        }

        $routeTemplate->delete();
        return back()->with('success', 'Template rute dihapus.');
    }

    public function generate()
    {
        $api = new RemoteApi();
        try {
            $r = $api->post('/admin/route-templates/generate');
            if ($r->successful()) {
                return back()->with('success', 'Generate jadwal selesai!');
            }
        } catch (\Exception $e) {
        }

        Artisan::call('schedules:generate');
        $output = Artisan::output();
        return back()->with('success', 'Generate jadwal selesai! ' . strip_tags($output));
    }
}
