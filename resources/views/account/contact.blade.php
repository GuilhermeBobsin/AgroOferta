<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Meu contato e localização</h2></x-slot>

    <div class="max-w-2xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('account.update') }}" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-7 space-y-6">
            @csrf @method('PATCH')

            <div class="space-y-1">
                <h3 class="font-semibold text-gray-900">Contato</h3>
                <label for="phone" class="block text-sm font-medium text-gray-700">WhatsApp com DDD</label>
                <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="(51) 99999-9999" autocomplete="tel" inputmode="tel" class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
                @error('phone') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                <p class="text-xs leading-5 text-gray-500">Seu número fica visível para usuários conectados nos seus anúncios ativos.</p>
            </div>

            <div class="space-y-3">
                <div>
                    <h3 class="font-semibold text-gray-900">Sua localização</h3>
                    <p class="mt-1 text-sm text-gray-500">Informe cidade e estado para encontrar insumos da sua região.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="city" class="mb-1 block text-sm font-medium text-gray-700">Cidade</label>
                        <input id="city" name="city" value="{{ old('city', $user->city) }}" autocomplete="address-level2" placeholder="Ex.: Santa Maria" class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
                        @error('city') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="state" class="mb-1 block text-sm font-medium text-gray-700">Estado (UF)</label>
                        <input id="state" name="state" value="{{ old('state', $user->state) }}" maxlength="2" autocomplete="address-level1" placeholder="RS" class="w-full rounded-lg border-gray-300 uppercase focus:border-green-700 focus:ring-green-700">
                        @error('state') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-green-50 p-4">
                <input type="hidden" name="latitude" id="lat" value="{{ old('latitude', $user->latitude) }}">
                <input type="hidden" name="longitude" id="lng" value="{{ old('longitude', $user->longitude) }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Ordenação por proximidade</h3>
                        <p id="geo-msg" aria-live="polite" class="mt-1 text-sm text-gray-600">
                            {{ $user->latitude !== null && $user->longitude !== null ? 'Coordenadas salvas.' : 'Coordenadas ainda não definidas.' }}
                        </p>
                    </div>
                    <button type="button" id="geo" class="shrink-0 rounded-lg border border-green-800 px-4 py-2 text-sm font-semibold text-green-900 transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-700">Usar minha localização</button>
                </div>
                <p class="mt-3 text-xs leading-5 text-gray-600">As coordenadas ajudam a ordenar anúncios por distância. A página mostra uma distância aproximada; o endereço exato não é exibido.</p>
                @error('latitude') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                @error('longitude') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <button class="w-full rounded-lg bg-green-800 py-3 font-semibold text-white transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">Salvar contato e localização</button>
        </form>
    </div>

    <script>
        document.getElementById('geo').addEventListener('click', () => {
            const msg = document.getElementById('geo-msg');
            if (!navigator.geolocation) { msg.textContent = 'Seu navegador não suporta localização.'; return; }
            msg.textContent = 'Obtendo localização...';
            navigator.geolocation.getCurrentPosition(
                (p) => {
                    document.getElementById('lat').value = p.coords.latitude.toFixed(7);
                    document.getElementById('lng').value = p.coords.longitude.toFixed(7);
                    msg.textContent = 'Localização obtida. Clique em Salvar.';
                },
                () => { msg.textContent = 'Não foi possível obter a localização (permissão negada?).'; }
            );
        });
    </script>
</x-app-layout>
