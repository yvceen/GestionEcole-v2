<x-admin-layout title="Modifier la route">
    <x-ui.page-header
        title="Modifier la route"
        :subtitle="$route->route_name"
    >
        <x-slot name="actions">
            <x-ui.button :href="route('admin.transport.routes.index')" variant="secondary">
                Retour
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card title="Circuit de transport" subtitle="Mettez à jour l'itinéraire, le véhicule et les arrêts.">
        <form method="POST" action="{{ route('admin.transport.routes.update', $route) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="app-field">
                <label class="app-label">Nom de la route</label>
                <input type="text" name="route_name" value="{{ old('route_name', $route->route_name) }}" class="app-input @error('route_name') border-rose-500 @enderror" required>
                @error('route_name')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="app-field">
                <label class="app-label">Véhicule</label>
                <select name="vehicle_id" class="app-input @error('vehicle_id') border-rose-500 @enderror">
                    <option value="">Aucun véhicule</option>
                    @foreach(($vehicles ?? collect()) as $vehicle)
                        <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $route->vehicle_id) == $vehicle->id)>
                            {{ $vehicle->registration_number }} ({{ $vehicle->vehicle_type }} - Cap. {{ $vehicle->capacity }})
                        </option>
                    @endforeach
                </select>
                @error('vehicle_id')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Point de départ</label>
                    <input type="text" name="start_point" value="{{ old('start_point', $route->start_point) }}" class="app-input @error('start_point') border-rose-500 @enderror" required>
                    @error('start_point')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Point d'arrivée</label>
                    <input type="text" name="end_point" value="{{ old('end_point', $route->end_point) }}" class="app-input @error('end_point') border-rose-500 @enderror" required>
                    @error('end_point')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="app-field">
                    <label class="app-label">Distance (km)</label>
                    <input type="number" name="distance_km" value="{{ old('distance_km', $route->distance_km) }}" step="0.1" min="0" class="app-input @error('distance_km') border-rose-500 @enderror">
                    @error('distance_km')<p class="app-error">{{ $message }}</p>@enderror
                </div>

                <div class="app-field">
                    <label class="app-label">Temps estimé (minutes)</label>
                    <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes', $route->estimated_minutes) }}" min="1" class="app-input @error('estimated_minutes') border-rose-500 @enderror">
                    @error('estimated_minutes')<p class="app-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="app-field">
                <label class="app-label">Tarif mensuel (DH)</label>
                <input type="number" name="monthly_fee" value="{{ old('monthly_fee', $route->monthly_fee) }}" step="0.01" min="0" class="app-input @error('monthly_fee') border-rose-500 @enderror" required>
                @error('monthly_fee')<p class="app-error">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-sky-700" {{ old('is_active', $route->is_active) ? 'checked' : '' }}>
                    Route active
                </label>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Arrêts de la route</p>
                        <p class="mt-1 text-xs text-slate-500">Cliquez sur la carte pour ajouter un arrêt, glissez pour réordonner ou déplacer.</p>
                    </div>
                </div>

                @error('stops')
                    <p class="mt-3 text-xs text-rose-600">{{ $message }}</p>
                @enderror

                <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div id="routeMap" class="h-[320px]"></div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="mb-2 text-xs font-semibold text-slate-500">Liste des arrêts</div>
                        <div id="stopsList" class="space-y-2"></div>
                    </div>
                </div>
                <input type="hidden" name="stops" id="stopsInput" value="{{ old('stops', json_encode($route->stops->map(fn($s)=>['name'=>$s->name,'lat'=>$s->lat,'lng'=>$s->lng,'scheduled_time'=>$s->scheduled_time,'notes'=>$s->notes])->values())) }}">
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit" variant="primary">Mettre à jour</x-ui.button>
                <x-ui.button :href="route('admin.transport.routes.index')" variant="secondary">Annuler</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function(){
            const map = L.map('routeMap').setView([33.9716, -6.8498], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            const stops = JSON.parse(document.getElementById('stopsInput').value || '[]');
            const markers = [];
            let polyline = L.polyline([], { color: '#0f172a' }).addTo(map);

            function syncInput() {
                document.getElementById('stopsInput').value = JSON.stringify(stops);
            }

            function refreshPolyline() {
                const latlngs = stops.map(s => [s.lat, s.lng]);
                polyline.setLatLngs(latlngs);
                if (latlngs.length) {
                    map.fitBounds(polyline.getBounds().pad(0.2));
                }
            }

            function renderList() {
                const list = document.getElementById('stopsList');
                list.innerHTML = '';
                stops.forEach((s, idx) => {
                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2';
                    row.draggable = true;
                    row.dataset.index = idx;
                    row.innerHTML = `
                        <span class="w-6 text-xs font-semibold text-slate-500">${idx + 1}</span>
                        <input class="flex-1 rounded border border-slate-200 px-2 py-1 text-sm"
                               value="${s.name ?? ('Stop ' + (idx + 1))}" />
                        <input type="time" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm"
                               value="${s.scheduled_time ?? ''}" />
                        <button type="button" class="text-xs text-rose-600">Supprimer</button>
                    `;
                    row.querySelector('input').addEventListener('input', (e) => {
                        s.name = e.target.value;
                        syncInput();
                    });
                    row.querySelectorAll('input')[1].addEventListener('input', (e) => {
                        s.scheduled_time = e.target.value || null;
                        syncInput();
                    });
                    row.querySelector('button').addEventListener('click', () => {
                        map.removeLayer(markers[idx]);
                        stops.splice(idx, 1);
                        markers.splice(idx, 1);
                        renderMarkers();
                        renderList();
                        refreshPolyline();
                        syncInput();
                    });
                    row.addEventListener('dragstart', (e) => {
                        e.dataTransfer.setData('text/plain', idx.toString());
                    });
                    row.addEventListener('dragover', (e) => e.preventDefault());
                    row.addEventListener('drop', (e) => {
                        e.preventDefault();
                        const from = parseInt(e.dataTransfer.getData('text/plain'), 10);
                        const to = idx;
                        if (from === to) return;
                        const moved = stops.splice(from, 1)[0];
                        stops.splice(to, 0, moved);
                        renderMarkers();
                        renderList();
                        refreshPolyline();
                        syncInput();
                    });

                    list.appendChild(row);
                });
            }

            function renderMarkers() {
                markers.forEach(m => map.removeLayer(m));
                markers.length = 0;
                stops.forEach((s) => {
                    const m = L.marker([s.lat, s.lng], { draggable: true }).addTo(map);
                    m.on('dragend', () => {
                        const pos = m.getLatLng();
                        s.lat = +pos.lat.toFixed(7);
                        s.lng = +pos.lng.toFixed(7);
                        refreshPolyline();
                        syncInput();
                    });
                    markers.push(m);
                });
            }

            map.on('click', (e) => {
                stops.push({
                    name: `Stop ${stops.length + 1}`,
                    lat: +e.latlng.lat.toFixed(7),
                    lng: +e.latlng.lng.toFixed(7),
                    scheduled_time: null,
                });
                renderMarkers();
                renderList();
                refreshPolyline();
                syncInput();
            });

            renderMarkers();
            renderList();
            refreshPolyline();
            syncInput();
        })();
    </script>
</x-admin-layout>
