
@extends('admin.layout')

@section('title', 'Edit Jadwal')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.schedules') }}" class="material-symbols-outlined text-primary p-2 hover:bg-gray-100 rounded-full">arrow_back</a>
        <h2 class="text-2xl font-bold text-primary">Edit Jadwal Perjalanan</h2>
    </div>

    <!-- Display Validation Errors -->
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="glass-card rounded-xl p-8 shadow-lg bg-white border border-outline-variant">
        <form action="{{ route('admin.schedules.update', data_get($schedule, 'id')) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-primary mb-2">Asal</label>
                    <input type="text" name="origin" value="{{ old('origin', data_get($schedule, 'origin')) }}" class="w-full border border-outline-variant rounded-lg p-3 focus:ring-primary focus:border-primary" placeholder="Contoh: Jakarta" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-primary mb-2">Tujuan</label>
                    <input type="text" name="destination" value="{{ old('destination', data_get($schedule, 'destination')) }}" class="w-full border border-outline-variant rounded-lg p-3 focus:ring-primary focus:border-primary" placeholder="Contoh: Bandung" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-primary mb-2">Pilih Kendaraan</label>
                    <select name="vehicle_id" class="w-full border border-outline-variant rounded-lg p-3 focus:ring-primary focus:border-primary" required>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ data_get($vehicle, 'id') }}" {{ old('vehicle_id', data_get($schedule, 'vehicle_id')) == data_get($vehicle, 'id') ? 'selected' : '' }}>
                                {{ data_get($vehicle, 'name', '-') }} ({{ data_get($vehicle, 'license_plate', '-') }}) - Kapasitas: {{ data_get($vehicle, 'capacity', '-') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-primary mb-2">Pilih Supir</label>
                    <select name="driver_id" class="w-full border border-outline-variant rounded-lg p-3 focus:ring-primary focus:border-primary" required>
                        @foreach($drivers as $driver)
                            <option value="{{ data_get($driver, 'id') }}" {{ old('driver_id', data_get($schedule, 'driver_id')) == data_get($driver, 'id') ? 'selected' : '' }}>
                                {{ data_get($driver, 'name', '-') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-primary mb-2">Waktu Keberangkatan</label>
                @php $schedDt = data_get($schedule, 'departure_time'); @endphp
                <input type="datetime-local" name="departure_time" value="{{ old('departure_time', $schedDt ? \Carbon\Carbon::parse($schedDt)->format('Y-m-d\\TH:i') : '') }}" class="w-full border border-outline-variant rounded-lg p-3 focus:ring-primary focus:border-primary" required>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-primary text-white py-4 rounded-lg font-bold text-lg shadow-md hover:opacity-90 transition-all">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
