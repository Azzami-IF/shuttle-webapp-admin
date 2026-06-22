
@extends('admin.layout')

@section('title', 'Monitoring Perjalanan')

@section('content')
<!-- Include Leaflet Assets -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold text-primary">Monitoring Perjalanan</h1>
        <p class="text-sm text-on-surface-variant">Pantau posisi armada bus aktif, rute historis, dan status operasional supir di lapangan.</p>
    </div>

    <!-- Active Trips Map & Passenger Details (2-column layout) -->
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Peta Kiri -->
        <div class="flex-grow bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-bold text-base mb-3 text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">map</span>
                Peta Pelacakan Armada Aktif (Real-Time & Historis)
            </h2>
            <div id="admin-map" style="width: 100%; height: 500px; border-radius: 8px; z-index: 1;"></div>
        </div>

        <!-- Passenger Details Kanan -->
        <div class="w-full lg:w-[400px] bg-white p-4 rounded-xl shadow-sm border border-gray-100 hidden flex-col" id="passenger-panel">
            <h2 class="font-bold text-base mb-3 text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">groups</span>
                Detail Penumpang
            </h2>
            <div class="flex flex-col gap-2 mb-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="text-xs font-semibold text-gray-500">TRIP ID <span id="panel-trip-id" class="text-gray-900 ml-2"></span></div>
                <div class="text-sm font-bold text-gray-800" id="panel-trip-route"></div>
                <div class="text-xs text-gray-600">Sopir: <span id="panel-trip-driver" class="font-medium text-gray-900"></span></div>
                <div class="text-xs text-gray-600">Unit: <span id="panel-trip-vehicle" class="font-medium text-gray-900"></span></div>
                <div class="mt-1"><span id="panel-trip-status"></span></div>
            </div>

            <div class="flex-grow overflow-y-auto">
                <h3 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Daftar Penumpang</h3>
                <ul id="passenger-list" class="flex flex-col gap-2">
                    <!-- Dinamis terisi dari JS -->
                </ul>
            </div>
        </div>
    </div>

    <!-- Trips History Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-3">
            <h2 class="font-bold text-base text-primary">Daftar Operasional Perjalanan</h2>
            <form method="GET" action="{{ route('admin.trips') }}" class="w-full md:w-48">
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Status</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="boarding" {{ request('status') === 'boarding' ? 'selected' : '' }}>Boarding</option>
                    <option value="on-going" {{ request('status') === 'on-going' ? 'selected' : '' }}>On-going</option>
                    <option value="arrived" {{ request('status') === 'arrived' ? 'selected' : '' }}>Arrived</option>
                    <option value="delayed" {{ request('status') === 'delayed' ? 'selected' : '' }}>Delayed</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-sm font-semibold text-on-surface-variant">
                        <th class="px-6 py-4">ID Perjalanan</th>
                        <th class="px-6 py-4">Pengemudi & Unit</th>
                        <th class="px-6 py-4">Rute</th>
                        <th class="px-6 py-4">Keberangkatan</th>
                        <th class="px-6 py-4">Lokasi Terkini</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($trips as $trip)
                        @php
                            $latestLoc = $trip->locations->last();
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="focusTripOnMap({{ $trip->id }})">
                            <td class="px-6 py-4 font-semibold">#TRP{{ $trip->id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ data_get($trip, 'schedule.driver.name', '-') }}</div>
                                <div class="text-xs text-gray-500">{{ data_get($trip, 'schedule.vehicle.name', '-') }} ({{ data_get($trip, 'schedule.vehicle.license_plate', '-') }})</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $trip->schedule?->origin }} → {{ $trip->schedule?->destination }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">{{ \Carbon\Carbon::parse($trip->schedule?->departure_time)->format('H:mm') }}</div>
                                <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($trip->schedule?->departure_time)->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                @if($latestLoc)
                                    <span class="text-secondary">{{ $latestLoc->latitude }}, {{ $latestLoc->longitude }}</span>
                                    <div class="text-[10px] text-gray-400">Update: {{ $latestLoc->created_at->format('H:mm:s') }}</div>
                                @else
                                    <span class="text-gray-400">Belum ada sinyal GPS</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($trip->status === 'scheduled')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Scheduled</span>
                                @elseif(in_array($trip->status, ['on-going', 'boarding', 'delayed', 'arrived']))
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 animate-pulse">{{ ucfirst($trip->status) }}</span>
                                @elseif($trip->status === 'completed')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <span class="material-symbols-outlined text-4xl block mb-2">route</span>
                                Tidak ada data perjalanan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // minimal map JS omitted for brevity
</script>
@endsection

@extends('admin.layout')

@section('title', 'Monitoring Perjalanan')

@section('content')
<!-- Include Leaflet Assets -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold text-primary">Monitoring Perjalanan</h1>
        <p class="text-sm text-on-surface-variant">Pantau posisi armada bus aktif, rute historis, dan status operasional supir di lapangan.</p>
    </div>

    <!-- Active Trips Map & Passenger Details (2-column layout) -->
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Peta Kiri -->
        <div class="flex-grow bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-bold text-base mb-3 text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">map</span>
                Peta Pelacakan Armada Aktif (Real-Time & Historis)
            </h2>
            <div id="admin-map" style="width: 100%; height: 500px; border-radius: 8px; z-index: 1;"></div>
        </div>

        <!-- Passenger Details Kanan -->
        <div class="w-full lg:w-[400px] bg-white p-4 rounded-xl shadow-sm border border-gray-100 hidden flex-col" id="passenger-panel">
            <h2 class="font-bold text-base mb-3 text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">groups</span>
                Detail Penumpang
            </h2>
            <div class="flex flex-col gap-2 mb-4 bg-gray-50 p-3 rounded-lg border border-gray-100">
                <div class="text-xs font-semibold text-gray-500">TRIP ID <span id="panel-trip-id" class="text-gray-900 ml-2"></span></div>
                <div class="text-sm font-bold text-gray-800" id="panel-trip-route"></div>
                <div class="text-xs text-gray-600">Sopir: <span id="panel-trip-driver" class="font-medium text-gray-900"></span></div>
                <div class="text-xs text-gray-600">Unit: <span id="panel-trip-vehicle" class="font-medium text-gray-900"></span></div>
                <div class="mt-1"><span id="panel-trip-status"></span></div>
            </div>

            <div class="flex-grow overflow-y-auto">
                <h3 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Daftar Penumpang</h3>
                <ul id="passenger-list" class="flex flex-col gap-2">
                    <!-- Dinamis terisi dari JS -->
                </ul>
            </div>
        </div>
    </div>

    <!-- Trips History Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
        <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-3">
            <h2 class="font-bold text-base text-primary">Daftar Operasional Perjalanan</h2>
            <form method="GET" action="{{ route('admin.trips') }}" class="w-full md:w-48">
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Status</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="boarding" {{ request('status') === 'boarding' ? 'selected' : '' }}>Boarding</option>
                    <option value="on-going" {{ request('status') === 'on-going' ? 'selected' : '' }}>On-going</option>
                    <option value="arrived" {{ request('status') === 'arrived' ? 'selected' : '' }}>Arrived</option>
                    <option value="delayed" {{ request('status') === 'delayed' ? 'selected' : '' }}>Delayed</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-sm font-semibold text-on-surface-variant">
                        <th class="px-6 py-4">ID Perjalanan</th>
                        <th class="px-6 py-4">Pengemudi & Unit</th>
                        <th class="px-6 py-4">Rute</th>
                        <th class="px-6 py-4">Keberangkatan</th>
                        <th class="px-6 py-4">Lokasi Terkini</th>
                        <th class="px-6 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($trips as $trip)
                        @php
                            $latestLoc = $trip->locations->last();
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="focusTripOnMap({{ $trip->id }})">
                            <td class="px-6 py-4 font-semibold">#TRP{{ $trip->id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ data_get($trip, 'schedule.driver.name', '-') }}</div>
                                <div class="text-xs text-gray-500">{{ data_get($trip, 'schedule.vehicle.name', '-') }} ({{ data_get($trip, 'schedule.vehicle.license_plate', '-') }})</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $trip->schedule?->origin }} → {{ $trip->schedule?->destination }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">{{ \Carbon\Carbon::parse($trip->schedule?->departure_time)->format('H:mm') }}</div>
                                <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($trip->schedule?->departure_time)->format('d M Y') }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                @if($latestLoc)
                                    <span class="text-secondary">{{ $latestLoc->latitude }}, {{ $latestLoc->longitude }}</span>
                                    <div class="text-[10px] text-gray-400">Update: {{ $latestLoc->created_at->format('H:mm:s') }}</div>
                                @else
                                    <span class="text-gray-400">Belum ada sinyal GPS</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($trip->status === 'scheduled')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Scheduled</span>
                                @elseif(in_array($trip->status, ['on-going', 'boarding', 'delayed', 'arrived']))
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 animate-pulse">{{ ucfirst($trip->status) }}</span>
                                @elseif($trip->status === 'completed')
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <span class="material-symbols-outlined text-4xl block mb-2">route</span>
                                Tidak ada data perjalanan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // minimal map JS omitted for brevity
</script>
@endsection
