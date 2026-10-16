@props(['city' => '', 'state' => '', 'required' => false, 'prefix' => 'location'])

@php
    $selectedState = old('state', strtoupper((string) $state));
    $selectedCity = old('city', $city);
@endphp

<div data-location-picker data-cities-url="{{ asset('data/municipalities.json') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div>
        <label for="{{ $prefix }}-state" class="mb-1 block text-sm font-medium text-gray-700">Estado (UF) @if ($required)<span class="text-red-700">*</span>@endif</label>
        <select id="{{ $prefix }}-state" name="state" autocomplete="address-level1" @if ($required) required @endif class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700">
            <option value="">Selecione o estado</option>
            @foreach (\App\Support\BrazilLocation::STATES as $code => $name)
                <option value="{{ $code }}" @selected($selectedState === $code)>{{ $code }} — {{ $name }}</option>
            @endforeach
        </select>
        @error('state') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $prefix }}-city" class="mb-1 block text-sm font-medium text-gray-700">Cidade @if ($required)<span class="text-red-700">*</span>@endif</label>
        <input id="{{ $prefix }}-city" name="city" value="{{ $selectedCity }}" list="{{ $prefix }}-city-options" autocomplete="off" maxlength="100" placeholder="Selecione primeiro o estado" @if ($required) required @endif @disabled(! $selectedState) class="w-full rounded-lg border-gray-300 focus:border-green-700 focus:ring-green-700 disabled:cursor-not-allowed disabled:bg-gray-100">
        <datalist id="{{ $prefix }}-city-options"></datalist>
        <p data-city-help class="mt-1 text-xs text-gray-500">Selecione o estado e digite o início do nome da cidade.</p>
        @error('city') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
</div>

<script>
    document.querySelectorAll('[data-location-picker]').forEach((picker) => {
        const state = picker.querySelector('[name="state"]');
        const city = picker.querySelector('[name="city"]');
        const options = picker.querySelector('datalist');
        const help = picker.querySelector('[data-city-help]');
        let citiesByState;

        const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleUpperCase('pt-BR');

        const renderOptions = () => {
            const query = normalize(city.value.trim());
            const matches = (citiesByState?.[state.value] ?? [])
                .filter((name) => normalize(name).startsWith(query))
                .slice(0, 100);

            options.replaceChildren(...matches.map((name) => {
                const option = document.createElement('option');
                option.value = name;
                return option;
            }));
        };

        const loadCities = async () => {
            if (!state.value) {
                city.value = '';
                city.disabled = true;
                options.replaceChildren();
                help.textContent = 'Selecione primeiro o estado.';
                return;
            }

            city.disabled = true;
            help.textContent = 'Carregando cidades...';

            try {
                citiesByState ??= await fetch(picker.dataset.citiesUrl).then((response) => {
                    if (!response.ok) throw new Error('Não foi possível carregar as cidades.');
                    return response.json();
                });
                city.disabled = false;
                city.placeholder = 'Digite o início da cidade';
                help.textContent = 'Digite para ver as cidades que começam com as letras informadas.';
                renderOptions();
            } catch (error) {
                help.textContent = 'Não foi possível carregar as cidades. Atualize a página e tente novamente.';
            }
        };

        state.addEventListener('change', () => {
            city.value = '';
            loadCities();
        });
        city.addEventListener('input', renderOptions);

        if (state.value) loadCities();
    });
</script>
