<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Meu contato</h2>
        <p class="mt-1 text-sm text-gray-600">
            Gerencie seu WhatsApp e sua localização para facilitar as negociações.
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('account.update') }}"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-7 space-y-6">
        @csrf
        @method('PATCH')

        <div class="space-y-1">
            <h3 class="font-semibold text-gray-900">Contato</h3>
            <label for="phone" class="block text-sm font-medium text-gray-700">
                WhatsApp com DDD
            </label>
            <input
                id="phone"
                name="phone"
                value="{{ old('phone', $user->phone) }}"
                placeholder="(51) 99999-9999"
                autocomplete="tel"
                inputmode="tel"
                class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700"
            >
            @error('phone')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            <p class="text-xs leading-5 text-gray-500">
                Seu número fica visível para usuários conectados nos seus anúncios ativos.
            </p>
        </div>

        <div class="space-y-3">
            <div>
                <h3 class="font-semibold text-gray-900">Sua localização</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Informe cidade e estado para encontrar insumos da sua região.
                </p>
            </div>

            <x-brazil-location-fields
                :city="$user->city"
                :state="$user->state"
                prefix="contact-location"
            />

            @error('city')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('state')
                <p class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-xl bg-green-50 p-4">
            <input type="hidden" name="latitude" id="lat"
                   value="{{ old('latitude', $user->latitude) }}">
            <input type="hidden" name="longitude" id="lng"
                   value="{{ old('longitude', $user->longitude) }}">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">Ordenação por proximidade</h3>
                    <p id="geo-msg" aria-live="polite" class="mt-1 text-sm text-gray-600">
                        {{ $user->latitude !== null && $user->longitude !== null
                            ? 'Coordenadas salvas.'
                            : 'Coordenadas ainda não definidas.' }}
                    </p>
                </div>

                <button
                    type="button"
                    id="geo"
                    class="shrink-0 rounded-lg border border-green-800 px-4 py-2 text-sm font-semibold text-green-900 transition hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-700"
                >
                    Usar minha localização
                </button>
            </div>

            <p class="mt-3 text-xs leading-5 text-gray-600">
                As coordenadas ajudam a ordenar anúncios por distância. A página mostra uma
                distância aproximada; o endereço exato não é exibido.
            </p>

            @error('latitude')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
            @error('longitude')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-green-800 py-3 font-semibold text-white transition hover:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2"
        >
            Salvar contato e localização
        </button>
    </form>
</div>

<script>
    (() => {
        const button = document.getElementById('geo');
        const message = document.getElementById('geo-msg');
        const latitude = document.getElementById('lat');
        const longitude = document.getElementById('lng');

        if (!button || !message || !latitude || !longitude) return;

        button.addEventListener('click', () => {
            if (!navigator.geolocation) {
                message.textContent = 'Seu navegador não oferece geolocalização.';
                return;
            }

            message.textContent = 'Obtendo sua localização...';
            button.disabled = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latitude.value = position.coords.latitude;
                    longitude.value = position.coords.longitude;
                    message.textContent = 'Localização obtida. Salve as alterações para confirmar.';
                    button.disabled = false;
                },
                () => {
                    message.textContent = 'Não foi possível obter a localização. Verifique a permissão do navegador.';
                    button.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        });
    })();
</script>