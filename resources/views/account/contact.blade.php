<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Meu contato e localização</h2></x-slot>

    <div class="max-w-xl mx-auto p-4">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('account.update') }}" class="bg-white rounded-lg shadow p-4 space-y-4">
            @csrf @method('PATCH')

            <div>
                <label class="text-sm text-gray-600">WhatsApp (com DDD)</label>
                <input name="phone" value="{{ old('phone', $user->phone) }}" placeholder="(55) 99999-9999" class="w-full rounded-md border-gray-300">
                <p class="text-xs text-gray-500 mt-1">Aparece como botão "Falar no WhatsApp" nos seus anúncios.</p>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <input name="city" value="{{ old('city', $user->city) }}" placeholder="Cidade" class="col-span-2 rounded-md border-gray-300">
                <input name="state" value="{{ old('state', $user->state) }}" maxlength="2" placeholder="UF" class="rounded-md border-gray-300 uppercase">
            </div>

            <div>
                <input type="hidden" name="latitude" id="lat" value="{{ old('latitude', $user->latitude) }}">
                <input type="hidden" name="longitude" id="lng" value="{{ old('longitude', $user->longitude) }}">
                <button type="button" id="geo" class="px-3 py-2 border rounded-md text-sm">Usar minha localização</button>
                <span id="geo-msg" class="text-sm text-gray-500 ms-2">
                    {{ $user->latitude ? 'Localização salva.' : 'Não definida.' }}
                </span>
                <p class="text-xs text-gray-500 mt-1">Usada para ordenar por "mais próximos" e copiada para seus novos anúncios.</p>
            </div>

            <button class="w-full bg-green-700 text-white py-3 rounded-md">Salvar</button>
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
